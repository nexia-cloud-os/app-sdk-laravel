<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use InvalidArgumentException;
use Nexia\Events\ActorReference;
use Nexia\Events\EventDraft;
use Nexia\Events\LegalEntityScope;

/** PII-free cache invalidation signal emitted after an App projection commits. */
final class ResourceProjectionChanged
{
    public const EVENT_NAME = 'resource.projection.changed.v1';

    public const SCHEMA_VERSION = 1;

    public static function draft(
        string $appKey,
        string $resourceKey,
        string $resourceId,
        int $legalEntityId,
        ?string $correlationId = null,
        ?string $causationId = null,
    ): EventDraft {
        if (! ResourceRef::hasCanonicalReference($appKey, $resourceKey, $resourceId)) {
            throw new InvalidArgumentException('A resource projection change requires a canonical ResourceRef identity.');
        }

        return new EventDraft(
            eventName: self::EVENT_NAME,
            payload: [
                'schema_version' => self::SCHEMA_VERSION,
                'resource_ref' => [
                    'app_key' => $appKey,
                    'resource_key' => $resourceKey,
                    'resource_id' => $resourceId,
                ],
            ],
            legalEntityScope: LegalEntityScope::explicit($legalEntityId),
            actor: new ActorReference,
            aggregateType: $resourceKey,
            aggregateId: $resourceId,
            correlationId: $correlationId,
            causationId: $causationId,
            schemaVersion: self::SCHEMA_VERSION,
        );
    }
}
