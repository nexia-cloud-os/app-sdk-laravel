<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer;

use InvalidArgumentException;

final class TransferSchema
{
    /**
     * @param  list<array{key: string, label: string, type: string, required: bool, importable: bool, exportable: bool, unique_import_key: bool, export_permission?: string, export_sensitive?: bool, export_requires_purpose?: bool, export_requires_fresh_authentication?: bool, reference?: array{resource: string, match_by: string, identifier: string}}>  $columns
     */
    private function __construct(
        private readonly array $columns = [],
    ) {}

    public static function make(): self
    {
        return new self;
    }

    /** Restore an export-only public schema through the same authoring rules. */
    public static function fromExportArray(array $data): self
    {
        if (array_keys($data) !== ['columns'] || ! is_array($data['columns']) || ! array_is_list($data['columns'])
            || count($data['columns']) > 1000) throw new InvalidArgumentException('Invalid export schema.');
        $schema = self::make();
        foreach ($data['columns'] as $column) {
            if (! is_array($column)) throw new InvalidArgumentException('Invalid export column.');
            $schema = $schema->exportOnly(
                key: $column['key'], label: $column['label'], type: $column['type'],
                permission: $column['export_permission'] ?? null,
                sensitive: $column['export_sensitive'] ?? false,
                requiresPurpose: $column['export_requires_purpose'] ?? false,
                requiresFreshAuthentication: $column['export_requires_fresh_authentication'] ?? false,
            );
        }
        if (\Nexia\Support\CanonicalPayloadFingerprint::sha256($schema->toArray()) !== \Nexia\Support\CanonicalPayloadFingerprint::sha256($data)) {
            throw new InvalidArgumentException('Noncanonical export schema.');
        }
        return $schema;
    }

    /** Restore a cataloged model transfer schema without exposing its model class. */
    public static function fromArray(array $data): self
    {
        if (array_keys($data) !== ['columns'] || ! is_array($data['columns']) || ! array_is_list($data['columns'])
            || count($data['columns']) > 1000) {
            throw new InvalidArgumentException('Invalid transfer schema.');
        }

        $schema = self::make();
        foreach ($data['columns'] as $column) {
            if (! is_array($column)
                || ! is_string($column['key'] ?? null)
                || ! is_string($column['label'] ?? null)
                || ! is_string($column['type'] ?? null)
                || ! is_bool($column['required'] ?? null)
                || ! is_bool($column['importable'] ?? null)
                || ! is_bool($column['exportable'] ?? null)
                || ! is_bool($column['unique_import_key'] ?? null)) {
                throw new InvalidArgumentException('Invalid transfer column.');
            }
            $allowed = ['key', 'label', 'type', 'required', 'importable', 'exportable', 'unique_import_key',
                'export_permission', 'export_sensitive', 'export_requires_purpose',
                'export_requires_fresh_authentication', 'reference', 'template_header'];
            if (array_diff(array_keys($column), $allowed) !== []) {
                throw new InvalidArgumentException('Invalid transfer column keys.');
            }
            $reference = $column['reference'] ?? null;
            if ($reference !== null && (! is_array($reference) || array_keys($reference) !== ['resource', 'match_by', 'identifier']
                || array_filter($reference, 'is_string') !== $reference)) {
                throw new InvalidArgumentException('Invalid transfer reference.');
            }
            foreach (['export_sensitive', 'export_requires_purpose', 'export_requires_fresh_authentication'] as $key) {
                if (isset($column[$key]) && $column[$key] !== true) {
                    throw new InvalidArgumentException('Invalid transfer export flag.');
                }
            }
            if (isset($column['export_permission']) && ! is_string($column['export_permission'])) {
                throw new InvalidArgumentException('Invalid transfer export permission.');
            }
            if (isset($column['template_header']) && ! is_string($column['template_header'])) {
                throw new InvalidArgumentException('Invalid transfer template header.');
            }

            $schema = $schema->add(
                $column['key'], $column['label'], $column['type'], $column['required'],
                $column['importable'], $column['exportable'], $column['unique_import_key'],
                $column['export_permission'] ?? null, $column['export_sensitive'] ?? false,
                $column['export_requires_purpose'] ?? false, $column['export_requires_fresh_authentication'] ?? false,
                $reference, $column['template_header'] ?? null,
            );
        }
        if (\Nexia\Support\CanonicalPayloadFingerprint::sha256($schema->toArray())
            !== \Nexia\Support\CanonicalPayloadFingerprint::sha256($data)) {
            throw new InvalidArgumentException('Noncanonical transfer schema.');
        }

        return $schema;
    }

    public function field(
        string $key,
        ?string $label = null,
        string $type = 'string',
        bool $required = false,
        ?string $templateHeader = null,
    ): self {
        return $this->add($key, $label, $type, $required, importable: true, exportable: true, uniqueImportKey: false, templateHeader: $templateHeader);
    }

    public function key(
        string $key,
        ?string $label = null,
        string $type = 'string',
        bool $required = true,
        ?string $templateHeader = null,
    ): self {
        return $this->add($key, $label, $type, $required, importable: true, exportable: true, uniqueImportKey: true, templateHeader: $templateHeader);
    }

    /**
     * A column whose printed value has to become another record's identifier.
     *
     * A vendor writes `110-234-567890` or `(주)대한상사`; the schema wants a UUID. The
     * declaration says only *which* resource to look in and *which* of its columns
     * carries the printed form — the resolving itself belongs to the host, because
     * the resource being matched against is frequently one the declaring App does
     * not own.
     *
     * Declared rather than inferred. A host guessing which columns are references
     * would guess wrong in both directions: silently resolving a plain text column,
     * or leaving a genuine reference as unusable text that fails at commit.
     *
     * @param  string  $resource  the Resource Key to search
     * @param  string  $matchBy  the column on that resource holding the printed form
     * @param  string  $identifier  the column whose value this field wants
     */
    public function reference(
        string $key,
        ?string $label = null,
        string $resource = '',
        string $matchBy = '',
        string $identifier = 'public_id',
        bool $required = true,
        bool $uniqueImportKey = false,
    ): self {
        if (trim($resource) === '' || trim($matchBy) === '') {
            throw new InvalidArgumentException(
                "Transfer schema reference column [{$key}] must name both a resource and a match column."
            );
        }

        return $this->add(
            $key,
            $label,
            'string',
            $required,
            importable: true,
            exportable: true,
            uniqueImportKey: $uniqueImportKey,
            reference: [
                'resource' => trim($resource),
                'match_by' => trim($matchBy),
                'identifier' => trim($identifier),
            ],
        );
    }

    public function exportOnly(
        string $key,
        ?string $label = null,
        string $type = 'string',
        ?string $permission = null,
        bool $sensitive = false,
        bool $requiresPurpose = false,
        bool $requiresFreshAuthentication = false,
    ): self {
        return $this->add(
            $key,
            $label,
            $type,
            required: false,
            importable: false,
            exportable: true,
            uniqueImportKey: false,
            exportPermission: $permission,
            exportSensitive: $sensitive,
            exportRequiresPurpose: $requiresPurpose,
            exportRequiresFreshAuthentication: $requiresFreshAuthentication,
        );
    }

    public function importOnly(
        string $key,
        ?string $label = null,
        string $type = 'string',
        bool $required = false,
        ?string $templateHeader = null,
    ): self {
        return $this->add($key, $label, $type, $required, importable: true, exportable: false, uniqueImportKey: false, templateHeader: $templateHeader);
    }

    private function add(
        string $key,
        ?string $label,
        string $type,
        bool $required,
        bool $importable,
        bool $exportable,
        bool $uniqueImportKey,
        ?string $exportPermission = null,
        bool $exportSensitive = false,
        bool $exportRequiresPurpose = false,
        bool $exportRequiresFreshAuthentication = false,
        ?array $reference = null,
        ?string $templateHeader = null,
    ): self {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('Transfer schema column keys must be non-empty.');
        }

        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $key)) {
            throw new InvalidArgumentException("Transfer schema column [{$key}] must be a safe attribute identifier.");
        }

        if ($this->findColumn($key) !== null) {
            throw new InvalidArgumentException("Transfer schema column [{$key}] is already declared.");
        }

        if ($uniqueImportKey && $this->uniqueImportKeyColumn() !== null) {
            throw new InvalidArgumentException("Transfer schema already declares a unique import key [{$this->uniqueImportKeyColumn()}].");
        }

        $column = [
            'key' => $key,
            'label' => $label ?? $key,
            'type' => $type,
            'required' => $required,
            'importable' => $importable,
            'exportable' => $exportable,
            'unique_import_key' => $uniqueImportKey,
        ];

        if ($exportPermission !== null) {
            $column['export_permission'] = $exportPermission;
        }

        if ($exportSensitive) {
            $column['export_sensitive'] = true;
        }

        if ($exportRequiresPurpose) {
            $column['export_requires_purpose'] = true;
        }

        if ($exportRequiresFreshAuthentication) {
            $column['export_requires_fresh_authentication'] = true;
        }

        if ($reference !== null) {
            $column['reference'] = $reference;
        }

        if ($templateHeader !== null) {
            $templateHeader = trim($templateHeader);
            if ($templateHeader === '') {
                throw new InvalidArgumentException("Transfer schema column [{$key}] template header must be non-empty.");
            }
            $column['template_header'] = $templateHeader;
        }

        return new self([
            ...$this->columns,
            $column,
        ]);
    }

    private function uniqueImportKeyColumn(): ?string
    {
        foreach ($this->columns as $column) {
            if ($column['unique_import_key']) {
                return $column['key'];
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, label: string, type: string, required: bool, importable: bool, exportable: bool, unique_import_key: bool, export_permission?: string, reference?: array{resource: string, match_by: string, identifier: string}}>
     */
    public function columns(): array
    {
        return $this->columns;
    }

    public function hasColumns(): bool
    {
        return $this->columns !== [];
    }

    /** @return array{columns: list<array{key: string, label: string, type: string, required: bool, importable: bool, exportable: bool, unique_import_key: bool, export_permission?: string, reference?: array{resource: string, match_by: string, identifier: string}}>} */
    public function toArray(): array
    {
        return ['columns' => $this->columns];
    }

    public function hash(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{key: string, label: string, type: string, required: bool, importable: bool, exportable: bool, unique_import_key: bool, export_permission?: string, reference?: array{resource: string, match_by: string, identifier: string}}|null
     */
    private function findColumn(string $key): ?array
    {
        foreach ($this->columns as $column) {
            if ($column['key'] === $key) {
                return $column;
            }
        }

        return null;
    }
}
