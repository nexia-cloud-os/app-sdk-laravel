<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;

/**
 * App-contributed document display schema for a structured Approval snapshot.
 *
 * The App declares an ordered set of labeled sections and fields; the host
 * renders that immutable submitted evidence. Supported field types are
 * string, longtext, date, datetime, number, currency, enum, and table.
 */
final class ApprovalDocumentSchema implements AppDescriptor
{
    public const FIELD_TYPES = ['string', 'longtext', 'date', 'datetime', 'number', 'currency', 'enum', 'table'];

    /** Composite descriptor key `{appKey}.{resourceKey}`. */
    public readonly string $key;

    /**
     * @param  list<array{title_key?: string, fields: list<array<string, mixed>>}>  $sections
     */
    public function __construct(
        public readonly string $appKey,
        public readonly string $resourceKey,
        public readonly array $sections,
        public readonly string $version = '1.0',
        public readonly DescriptorStatus $status = DescriptorStatus::Active,
        public readonly ?string $titleKey = null,
    ) {
        if (trim($appKey) === '') {
            throw new \InvalidArgumentException('ApprovalDocumentSchema app_key must be a non-empty string.');
        }
        if (trim($resourceKey) === '') {
            throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}] resource_key must be a non-empty string.");
        }
        if ($sections === []) {
            throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] must declare at least one section.");
        }

        foreach ($sections as $section) {
            $fields = $section['fields'] ?? null;
            if (! is_array($fields) || $fields === []) {
                throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] each section must declare a non-empty fields list.");
            }
            foreach ($fields as $field) {
                $this->assertValidField($appKey, $resourceKey, $field);
            }
        }

        $this->key = trim($appKey).'.'.trim($resourceKey);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function assertValidField(string $appKey, string $resourceKey, array $field): void
    {
        $key = $field['key'] ?? null;
        if (! is_string($key) || trim($key) === '') {
            throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] every field needs a non-empty key.");
        }

        $type = $field['type'] ?? 'string';
        if (! in_array($type, self::FIELD_TYPES, true)) {
            throw new \InvalidArgumentException(
                "ApprovalDocumentSchema [{$appKey}.{$resourceKey}] field [{$key}] type [{$type}] must be one of: ".implode(', ', self::FIELD_TYPES),
            );
        }

        if (($field['label_key'] ?? null) === null && $type !== 'table') {
            throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] field [{$key}] needs a label_key.");
        }

        if ($type === 'table') {
            $columns = $field['columns'] ?? null;
            if (! is_array($columns) || $columns === []) {
                throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] table field [{$key}] must declare columns.");
            }
            foreach ($columns as $column) {
                $columnKey = $column['key'] ?? null;
                if (! is_string($columnKey) || trim($columnKey) === '') {
                    throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] table [{$key}] every column needs a key.");
                }
                if (($column['label_key'] ?? null) === null) {
                    throw new \InvalidArgumentException("ApprovalDocumentSchema [{$appKey}.{$resourceKey}] table [{$key}] column [{$columnKey}] needs a label_key.");
                }
            }
        }
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }
}
