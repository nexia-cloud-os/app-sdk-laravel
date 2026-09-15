<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceTransfer\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nexia\ResourceTransfer\TransferSchema;

trait ExportImportable
{
    public const TRANSFER_SCOPE_TENANT = 'tenant';

    public const TRANSFER_SCOPE_LEGAL_ENTITY = 'legal_entity';

    public static function transferColumns(TransferSchema $schema): TransferSchema
    {
        return $schema;
    }

    /**
     * Authorization scope for transfer (export/import).
     *
     * `tenant` (default) — the resource spans the whole tenant, so transfer is
     * a tenant-wide bulk operation gated on tenant-wide `system.data.*` plus the
     * model permission at scope 0.
     *
     * `legal_entity` — the resource is Legal-Entity-scoped (the list is already
     * filtered to what the actor can see). Transfer still requires the
     * tenant-wide `system.data.*` capability at scope 0, plus the model
     * permission at the actor's current legal entity scope, and the export is
     * narrowed by {@see transferExportQuery()} to the rows the actor may see.
     */
    public static function transferScope(): string
    {
        return self::TRANSFER_SCOPE_TENANT;
    }

    /**
     * Extra exact capabilities required in addition to the Resource read
     * permission and platform data-export permission.
     *
     * @return list<string>
     */
    public static function transferExportPermissionKeys(): array
    {
        return [];
    }

    /**
     * Base query the export reads from. The default exports every row; models
     * override this to widen (e.g. include soft-deleted) or, for
     * legal-entity-scoped resources, to narrow to the rows the acting user may
     * see — mirroring the same visibility scope the list endpoint applies.
     */
    public static function transferExportQuery(?Authenticatable $actor = null): Builder
    {
        return static::query();
    }

    /**
     * Create one record from a validated, cast set of importable attributes.
     *
     * The default builds the record directly from the import columns. Models
     * whose creation needs derived columns, defaults, or related records
     * (foreign keys set from request context, auto-created associations) should
     * override this — it runs inside the import loop's per-row try/catch, so an
     * override may open its own transaction and rely on the request actor and
     * tenant session, exactly as the resource's normal create path does.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createFromImport(array $attributes): Model
    {
        return static::query()->create($attributes);
    }
}
