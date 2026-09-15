<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models;

use Nexia\Laravel\Models\Concerns\UsesFilterableScoutSearch;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Persistence baseline for tenant-backed resource models.
 *
 * `NexiaModel` stays intentionally thin for special platform records
 * such as audit logs, outbox messages, and other non-resource models.
 * Tenant resource models extend this class only for shared Scout integration,
 * reversible deletion, and audited change history. Separate Resource Catalog
 * Contributors publish platform metadata; inheriting this persistence base
 * never publishes Permission, Navigation, Agent, or Dashboard contributions.
 */
abstract class NexiaEntityModel extends NexiaModel
{
    use LogsActivity;
    use SoftDeletes;
    use UsesFilterableScoutSearch;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('resource')
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->logExcept(['created_at', 'updated_at', 'deleted_at'])
            ->setDescriptionForEvent(
                fn (string $eventName): string => class_basename($this).' '.$eventName,
            );
    }
}
