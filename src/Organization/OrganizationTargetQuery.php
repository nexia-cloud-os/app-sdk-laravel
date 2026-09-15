<?php

declare(strict_types=1);

namespace Nexia\Organization;

/** Canonical direct Legal Entity and Operating Unit list-filter intent. */
final readonly class OrganizationTargetQuery
{
    /**
     * @param  list<string>|null  $legalEntityPublicIds null means unrestricted
     * @param  list<string>|null  $operatingUnitPublicIds null means unrestricted
     */
    private function __construct(
        public ?array $legalEntityPublicIds,
        public ?array $operatingUnitPublicIds,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromInput(array $input): self
    {
        self::assertNoUnsupportedOrganizationTargetKeys($input);

        return new self(
            self::publicIds($input, 'legal_entity_public_ids'),
            self::publicIds($input, 'operating_unit_public_ids'),
        );
    }

    /** @param array<string, mixed> $input */
    private static function assertNoUnsupportedOrganizationTargetKeys(array $input): void
    {
        foreach ([
            'legal_entity_public_id',
            'legal_entity_public_id[]',
            'legal_entity_public_ids[]',
            'operating_unit_public_id',
            'operating_unit_public_id[]',
            'operating_unit_public_ids[]',
        ] as $key) {
            if (array_key_exists($key, $input)) {
                throw new OrganizationTargetQueryException($key, "Organization target query [{$key}] is not a supported list filter.");
            }
        }
    }

    public function isUnrestricted(): bool
    {
        return $this->legalEntityPublicIds === null
            && $this->operatingUnitPublicIds === null;
    }

    /** @param array<string, mixed> $input @return list<string>|null */
    private static function publicIds(array $input, string $key): ?array
    {
        if (! array_key_exists($key, $input)) {
            return null;
        }

        $values = $input[$key];
        if (! is_array($values)) {
            throw new OrganizationTargetQueryException($key, "Organization target query [{$key}] must be an array.");
        }

        $ids = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new OrganizationTargetQueryException($key, "Organization target query [{$key}] must contain UUIDs.");
            }

            if (trim($value) !== $value || ! self::isUuid($value)) {
                throw new OrganizationTargetQueryException($key, "Organization target query [{$key}] must contain non-empty UUIDs.");
            }

            $normalized = strtolower($value);
            if (array_key_exists($normalized, $ids)) {
                throw new OrganizationTargetQueryException($key, "Organization target query [{$key}] cannot contain duplicate UUIDs.");
            }
            $ids[$normalized] = true;
        }

        if ($ids === []) {
            throw new OrganizationTargetQueryException($key, "Organization target query [{$key}] cannot be empty.");
        }

        $ids = array_keys($ids);
        sort($ids, SORT_STRING);

        return $ids;
    }

    private static function isUuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value) === 1;
    }
}
