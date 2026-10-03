<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database;

use InvalidArgumentException;

/** Bounded insert-only integration target inside the caller App's own schema. */
final readonly class ExternalDatabaseWriterDefinition
{
    /** @param list<string> $insertColumns */
    public function __construct(
        public string $key,
        public string $table,
        public array $insertColumns,
        public string $permission,
        public string $readPermission,
    ) {
        foreach ([$key, $table] as $identifier) {
            if (preg_match('/\A[a-z][a-z0-9_]{0,62}\z/D', $identifier) !== 1) {
                throw new InvalidArgumentException('External database writer identifier is invalid.');
            }
        }
        if (preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $permission) !== 1
            || preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $readPermission) !== 1
            || $insertColumns === [] || count($insertColumns) > 64) {
            throw new InvalidArgumentException('External database writer definition is invalid.');
        }
        foreach ($insertColumns as $column) {
            if (! is_string($column) || preg_match('/\A[a-z][a-z0-9_]{0,62}\z/D', $column) !== 1) {
                throw new InvalidArgumentException('External database writer column is invalid.');
            }
        }
        if (count(array_unique($insertColumns, SORT_STRING)) !== count($insertColumns)) {
            throw new InvalidArgumentException('External database writer columns must be distinct.');
        }
    }

    /** @return array{key:string,table:string,insert_columns:list<string>,permission:string,read_permission:string} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'table' => $this->table, 'insert_columns' => $this->insertColumns,
            'permission' => $this->permission, 'read_permission' => $this->readPermission];
    }
}
