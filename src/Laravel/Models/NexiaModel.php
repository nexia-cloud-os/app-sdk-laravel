<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Nexia\Laravel\Models\Concerns\UsesAppDatabaseConnection;
use Nexia\Laravel\Models\Contracts\HostReferenceResolver;

/**
 * Shared backend model base for Nexia Eloquent models.
 *
 * `NexiaModel` is intentionally thin. It is a single attachment point for
 * future cross-cutting concerns that genuinely apply to every Nexia model,
 * not the first occupant of that attachment point. Behavior is opt-in
 * through traits under `Nexia\Laravel\Models\Concerns\*` (identity, lifecycle) or
 * domain-scoped namespaces (e.g. `App\Communications\Concerns\*`).
 *
 * Do NOT add `public_id`, `slug`, lifecycle, attachment, resource activity,
 * comment, or domain-specific behavior directly to this class. Those belong in
 * opt-in traits so that internal-only models (per doctrine 11's Internal
 * identifier rule) and append-only history rows are not forced to inherit
 * behavior they do not need.
 *
 * @see docs/reference/RESOURCE-SCAFFOLDING.md
 * @see docs/doctrine/11-RESOURCE-SCAFFOLDING.md
 */
abstract class NexiaModel extends Model implements HasMedia
{
    use UsesAppDatabaseConnection;
    use InteractsWithMedia {
        bootInteractsWithMedia as private bootLocalMedia;
        media as private localMediaRelation;
    }

    private static bool $localMediaEnabled = false;

    /** Core opts in before model boot; isolated App hosts use attachment contracts. */
    public static function enableLocalMedia(): void
    {
        self::$localMediaEnabled = true;
    }

    public static function bootInteractsWithMedia(): void
    {
        if (self::$localMediaEnabled) {
            static::bootLocalMedia();
        }
    }

    public function media(): MorphMany
    {
        if (! self::$localMediaEnabled) {
            throw new \LogicException('Local media is unavailable; use the host attachment contracts.');
        }

        return $this->localMediaRelation();
    }

    private static ?HostReferenceResolver $hostReferenceResolver = null;

    /** Configure the host-owned scalar reference resolver in the Laravel adapter. */
    public static function configureHostReferenceResolver(HostReferenceResolver $resolver): void
    {
        self::$hostReferenceResolver = $resolver;
    }

    public function getAttribute($key)
    {
        if (! is_string($key) || method_exists($this, $key) || array_key_exists($key, $this->getAttributes())) {
            return parent::getAttribute($key);
        }

        if ($this->relationLoaded($key)) {
            return $this->getRelation($key);
        }

        [$isHostReference, $foreignKey, $kind] = $this->hostReferenceDefinition($key);
        if (! $isHostReference) {
            return parent::getAttribute($key);
        }

        $resolver = self::$hostReferenceResolver;
        if ($resolver === null) {
            throw new \LogicException('Host reference resolver is not configured.');
        }

        $reference = match ($kind) {
            'actor' => $resolver->actorByKey($this->getAttributes()[$foreignKey]),
            'party' => $resolver->partyByKey($this->getAttributes()[$foreignKey]),
            'legal_entity' => $resolver->legalEntityByKey($this->getAttributes()[$foreignKey]),
            'operating_unit' => $resolver->operatingUnitByKey($this->getAttributes()[$foreignKey]),
        };

        return $reference;
    }

    /** @return array{bool, string, 'actor'|'party'|'legal_entity'|'operating_unit'} */
    private function hostReferenceDefinition(string $key): array
    {
        $attributes = $this->getAttributes();
        $definitions = [
            'party' => ['party_id', 'party'],
            'customerParty' => ['customer_party_id', 'party'],
            'contractingParty' => ['contracting_party_id', 'party'],
            'legalEntity' => ['legal_entity_id', 'legal_entity'],
            'targetLegalEntity' => ['target_legal_entity_id', 'legal_entity'],
            'operatingUnit' => ['operating_unit_id', 'operating_unit'],
            'owningOperatingUnit' => ['owning_operating_unit_id', 'operating_unit'],
            'proposedOperatingUnit' => ['proposed_operating_unit_id', 'operating_unit'],
            'targetOperatingUnit' => ['target_operating_unit_id', 'operating_unit'],
        ];

        if (isset($definitions[$key])) {
            [$foreignKey, $kind] = $definitions[$key];

            return [array_key_exists($foreignKey, $attributes), $foreignKey, $kind];
        }

        $snakeKey = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
        foreach (["{$snakeKey}_user_id", "{$snakeKey}_actor_id", "{$snakeKey}_id"] as $foreignKey) {
            if (array_key_exists($foreignKey, $attributes)) {
                return [true, $foreignKey, 'actor'];
            }
        }

        return [false, '', 'actor'];
    }
}
