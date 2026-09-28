<?php

namespace GraphQL;

use Generator;
use InvalidArgumentException;
use UnexpectedValueException;

/** Describes how a GraphQL field exposes its pages. */
final readonly class Pagination
{
    private const string LIGHTHOUSE_PAGES = 'lighthouse_pages';
    private const string LIGHTHOUSE_CONNECTION = 'lighthouse_connection';
    private const string OFFSET = 'offset';

    /** @param list<string> $fieldPath */
    private function __construct(
        private string $mode,
        private array $fieldPath,
        private int $pageSize,
        private int|string|null $start
    ) {
    }

    public static function lighthousePages(string $fieldPath, int $pageSize, int $startPage = 1): self
    {
        if ($startPage < 1) {
            throw new InvalidArgumentException('The starting page must be at least 1');
        }

        return new self(self::LIGHTHOUSE_PAGES, self::parsePath($fieldPath), self::positiveSize($pageSize), $startPage);
    }

    public static function lighthouseConnection(string $fieldPath, int $pageSize, ?string $startAfter = null): self
    {
        return new self(
            self::LIGHTHOUSE_CONNECTION,
            self::parsePath($fieldPath),
            self::positiveSize($pageSize),
            $startAfter
        );
    }

    public static function offset(string $fieldPath, int $pageSize, int $startOffset = 0): self
    {
        if ($startOffset < 0) {
            throw new InvalidArgumentException('The starting offset must not be negative');
        }

        return new self(self::OFFSET, self::parsePath($fieldPath), self::positiveSize($pageSize), $startOffset);
    }

    /**
     * @param array<string, mixed> $variables
     * @return Generator<int, array<string, mixed>>
     */
    public function iterate(Client $client, string $query, array $variables): Generator
    {
        $controlledVariables = $this->mode === self::OFFSET ? ['limit', 'offset']
            : ($this->mode === self::LIGHTHOUSE_CONNECTION ? ['first', 'after'] : ['first', 'page']);
        foreach ($controlledVariables as $name) {
            if (array_key_exists($name, $variables)) {
                throw new InvalidArgumentException("Pagination controls the '$name' variable");
            }
        }

        $position = $this->start;
        while (true) {
            $pageVariables = $variables;
            if ($this->mode === self::OFFSET) {
                $pageVariables['limit'] = $this->pageSize;
                $pageVariables['offset'] = $position;
            } else {
                $pageVariables['first'] = $this->pageSize;
                $pageVariables[$this->mode === self::LIGHTHOUSE_CONNECTION ? 'after' : 'page'] = $position;
            }

            $data = $client->runRawQuery($query, true, $pageVariables)->getData();
            if (!is_array($data)) {
                throw new UnexpectedValueException('Paginated response must contain an object in data');
            }
            $field = $this->field($data);

            if ($this->mode === self::LIGHTHOUSE_CONNECTION) {
                $edges = self::listAt($field, 'edges');
                $items = [];
                foreach ($edges as $edge) {
                    if (!is_array($edge) || !is_array($edge['node'] ?? null)) {
                        throw new UnexpectedValueException('Paginated response must contain edges with object nodes');
                    }
                    $items[] = $edge['node'];
                }
                $info = self::objectAt($field, 'pageInfo');
                $hasMore = self::booleanAt($info, 'hasNextPage');
                $next = $info['endCursor'] ?? null;
                if ($hasMore && (!is_string($next) || $next === '' || $next === $position)) {
                    throw new UnexpectedValueException('pageInfo.endCursor must advance when hasNextPage is true');
                }
            } elseif ($this->mode === self::LIGHTHOUSE_PAGES) {
                $items = self::listAt($field, 'data');
                $info = self::objectAt($field, 'paginatorInfo');
                $hasMore = self::booleanAt($info, 'hasMorePages');
                if ($hasMore && $position === PHP_INT_MAX) {
                    throw new UnexpectedValueException('Pagination page number cannot advance');
                }
                $next = $position + 1;
            } else {
                $items = self::listAt($field, 'results');
                $total = $field['count'] ?? null;
                if (!is_int($total) || $total < 0) {
                    throw new UnexpectedValueException('Paginated response count must be a non-negative integer');
                }
                if ($position > PHP_INT_MAX - count($items)) {
                    throw new UnexpectedValueException('Pagination offset cannot advance');
                }
                $next = $position + count($items);
                if ($items !== [] && $next > $total) {
                    throw new UnexpectedValueException('Paginated response contains more records than count');
                }
                $hasMore = $next < $total;
            }

            foreach ($items as $item) {
                if (!is_array($item)) {
                    throw new UnexpectedValueException('Paginated records must be objects');
                }
            }
            if ($hasMore && $items === []) {
                throw new UnexpectedValueException('Paginated response cannot advance from an empty page');
            }

            foreach ($items as $item) {
                yield $item;
            }
            if (!$hasMore) {
                return;
            }
            $position = $next;
        }
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function field(array $data): array
    {
        $value = $data;
        foreach ($this->fieldPath as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                throw new UnexpectedValueException('Paginated field is missing: ' . implode('.', $this->fieldPath));
            }
            $value = $value[$segment];
        }
        if (!is_array($value)) {
            throw new UnexpectedValueException('Paginated field must be an object: ' . implode('.', $this->fieldPath));
        }

        return $value;
    }

    /** @param array<string, mixed> $object
     *  @return array<string, mixed>
     */
    private static function objectAt(array $object, string $key): array
    {
        $value = $object[$key] ?? null;
        if (!is_array($value) || array_is_list($value) && $value !== []) {
            throw new UnexpectedValueException("Paginated response must contain an object at '$key'");
        }

        return $value;
    }

    /** @param array<string, mixed> $object
     *  @return list<mixed>
     */
    private static function listAt(array $object, string $key): array
    {
        $value = $object[$key] ?? null;
        if (!is_array($value) || !array_is_list($value)) {
            throw new UnexpectedValueException("Paginated response must contain a list at '$key'");
        }

        return $value;
    }

    /** @param array<string, mixed> $object */
    private static function booleanAt(array $object, string $key): bool
    {
        $value = $object[$key] ?? null;
        if (!is_bool($value)) {
            throw new UnexpectedValueException("Paginated response must contain a boolean at '$key'");
        }

        return $value;
    }

    /** @return list<string> */
    private static function parsePath(string $path): array
    {
        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $segment)) {
                throw new InvalidArgumentException('The paginated field path must contain GraphQL field names');
            }
        }

        return $segments;
    }

    private static function positiveSize(int $pageSize): int
    {
        if ($pageSize < 1) {
            throw new InvalidArgumentException('The page size must be positive');
        }

        return $pageSize;
    }
}
