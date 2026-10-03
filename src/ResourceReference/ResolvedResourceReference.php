<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use DateTimeImmutable;
use InvalidArgumentException;
use SensitiveParameterValue;

/** Owner-produced public reference plus separately held safe and protected fields. */
final readonly class ResolvedResourceReference
{
    private SensitiveParameterValue $protectedValues;

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $protectedValues
     */
    public function __construct(
        public string $appKey,
        public string $resourceKey,
        public string $resourceId,
        public string $display,
        public ?string $href,
        public array $fields,
        public DateTimeImmutable $asOf,
        public ?int $revision = null,
        public ?string $state = null,
        array $protectedValues = [],
    ) {
        if (! ResourceRef::hasCanonicalReference($appKey, $resourceKey, $resourceId)
            || trim($display) === '') {
            throw new InvalidArgumentException('Resolved Resource References require a complete canonical identity.');
        }

        if ($revision !== null && $revision < 0) {
            throw new InvalidArgumentException('Resolved Resource Reference revisions must be non-negative.');
        }

        self::assertKeyedValues($fields);
        self::assertKeyedValues($protectedValues);

        $this->protectedValues = new SensitiveParameterValue($protectedValues);
    }

    /** Restore the public snapshot, rejecting protected values and altered content. */
    public static function fromSnapshot(array $snapshot): self
    {
        try {
            if (($snapshot['schema_version'] ?? null) !== 1 || array_key_exists('protected_values', $snapshot) || array_key_exists('protectedValues', $snapshot)) {
                throw new InvalidArgumentException;
            }
            $reference = $snapshot['reference'];
            $result = new self(
                $reference['app_key'], $reference['resource_key'], $reference['resource_id'], $reference['display'], $reference['href'],
                $snapshot['fields'], new DateTimeImmutable($snapshot['as_of']), $snapshot['revision'], $snapshot['status'],
            );
            if (! hash_equals($result->snapshot()['content_hash'], $snapshot['content_hash'])) {
                throw new InvalidArgumentException;
            }
            return $result;
        } catch (\Throwable) {
            throw new InvalidArgumentException('Invalid SDK resource snapshot.');
        }
    }

    /** @return array{app_key: string, resource_key: string, resource_id: string, display: string, href: string|null} */
    public function reference(): array
    {
        return [
            'app_key' => $this->appKey,
            'resource_key' => $this->resourceKey,
            'resource_id' => $this->resourceId,
            'display' => $this->display,
            'href' => $this->href,
        ];
    }

    /**
     * @return array{
     *   schema_version: 1,
     *   reference: array{app_key: string, resource_key: string, resource_id: string, display: string, href: string|null},
     *   revision: int|null,
     *   status: string|null,
     *   as_of: string,
     *   content_hash: string,
     *   fields: array<string, mixed>
     * }
     */
    public function snapshot(): array
    {
        $content = [
            'reference' => $this->reference(),
            'revision' => $this->revision,
            'status' => $this->state,
            'as_of' => $this->asOf->format(DATE_ATOM),
            'fields' => $this->fields,
        ];

        $hashContent = $content;
        unset($hashContent['as_of']);
        $canonical = self::canonicalize($hashContent);

        return [
            'schema_version' => 1,
            ...$content,
            'content_hash' => hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR)),
        ];
    }

    /** @return array<string, mixed> */
    public function protectedValues(): array
    {
        return (array) $this->protectedValues->getValue();
    }

    public function hasProtectedValues(): bool
    {
        return $this->protectedValues() !== [];
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        return [
            'appKey' => $this->appKey,
            'resourceKey' => $this->resourceKey,
            'resourceId' => $this->resourceId,
            'display' => $this->display,
            'href' => $this->href,
            'fields' => $this->fields,
            'asOf' => $this->asOf,
            'revision' => $this->revision,
            'state' => $this->state,
        ];
    }

    /** @param array<string, mixed> $data */
    public function __unserialize(array $data): void
    {
        if (array_keys($data) !== [
            'appKey',
            'resourceKey',
            'resourceId',
            'display',
            'href',
            'fields',
            'asOf',
            'revision',
            'state',
        ]) {
            throw new InvalidArgumentException('Serialized Resource Reference state is invalid.');
        }

        $restored = new self(
            appKey: $data['appKey'],
            resourceKey: $data['resourceKey'],
            resourceId: $data['resourceId'],
            display: $data['display'],
            href: $data['href'],
            fields: $data['fields'],
            asOf: $data['asOf'],
            revision: $data['revision'],
            state: $data['state'],
        );

        $this->appKey = $restored->appKey;
        $this->resourceKey = $restored->resourceKey;
        $this->resourceId = $restored->resourceId;
        $this->display = $restored->display;
        $this->href = $restored->href;
        $this->fields = $restored->fields;
        $this->asOf = $restored->asOf;
        $this->revision = $restored->revision;
        $this->state = $restored->state;
        $this->protectedValues = new SensitiveParameterValue([]);
    }

    /** @return array<string, mixed> */
    public function __debugInfo(): array
    {
        return $this->__serialize();
    }

    /** @param array<string, mixed> $values */
    private static function assertKeyedValues(array $values): void
    {
        foreach (array_keys($values) as $key) {
            if (! is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Resolved Resource Reference field keys must be non-empty strings.');
            }
        }

        self::assertSafeValue($values);
    }

    private static function assertSafeValue(mixed $value): void
    {
        if ($value === null || is_scalar($value)) {
            return;
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Resolved Resource Reference fields must contain JSON-safe values only.');
        }

        foreach ($value as $key => $child) {
            if (! is_int($key) && (! is_string($key) || trim($key) === '')) {
                throw new InvalidArgumentException('Resolved Resource Reference nested field keys must be integers or non-empty strings.');
            }
            self::assertSafeValue($child);
        }
    }

    private static function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $canonical = array_map(self::canonicalize(...), $value);
        if (! array_is_list($canonical)) {
            ksort($canonical);
        }

        return $canonical;
    }
}
