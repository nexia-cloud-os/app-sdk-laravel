<?php

declare(strict_types=1);

namespace Nexia\Permission;

use InvalidArgumentException;

/** Immutable recommendation for a non-granting role composition. */
final readonly class RolePreset
{
    /** @param list<string> $permissions */
    public function __construct(
        public string $key,
        public string $appKey,
        public int $version,
        public string $name,
        public string $description,
        public AssignmentScope $recommendedScope,
        public ?PresetType $type,
        public ?SubjectPopulation $subjectPopulation,
        public string $audience,
        public string $risk,
        public array $permissions,
        public bool $deprecated,
        public ?string $replacementKey,
    ) {}

    /** @param array<string, mixed> $entry */
    public static function fromArray(array $entry): self
    {
        $scope = AssignmentScope::tryFrom((string) ($entry['recommended_scope'] ?? ''));
        if ($scope === null) {
            throw new InvalidArgumentException('Role preset recommended_scope must be a known assignment scope.');
        }

        $type = array_key_exists('type', $entry) && $entry['type'] !== null
            ? PresetType::tryFrom((string) $entry['type'])
            : null;
        if (array_key_exists('type', $entry) && $entry['type'] !== null && $type === null) {
            throw new InvalidArgumentException('Role preset type must be a known preset type.');
        }

        $population = array_key_exists('subject_population', $entry) && $entry['subject_population'] !== null
            ? SubjectPopulation::tryFrom((string) $entry['subject_population'])
            : null;
        if (array_key_exists('subject_population', $entry) && $entry['subject_population'] !== null && $population === null) {
            throw new InvalidArgumentException('Role preset subject_population must be a known population.');
        }

        $permissions = $entry['permissions'] ?? [];
        if (! is_array($permissions) || array_filter($permissions, 'is_string') !== $permissions) {
            throw new InvalidArgumentException('Role preset permissions must be a list of strings.');
        }

        foreach (['key', 'app_key', 'name', 'description', 'audience', 'risk'] as $field) {
            if (! is_string($entry[$field] ?? null) || trim($entry[$field]) === '') {
                throw new InvalidArgumentException("Role preset [{$field}] must be a non-empty string.");
            }
        }

        return new self(
            key: $entry['key'],
            appKey: $entry['app_key'],
            version: (int) ($entry['version'] ?? 1),
            name: $entry['name'],
            description: $entry['description'],
            recommendedScope: $scope,
            type: $type,
            subjectPopulation: $population,
            audience: $entry['audience'],
            risk: $entry['risk'],
            permissions: array_values($permissions),
            deprecated: (bool) ($entry['deprecated'] ?? false),
            replacementKey: is_string($entry['replacement_key'] ?? null) ? $entry['replacement_key'] : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $entry = [
            'key' => $this->key,
            'app_key' => $this->appKey,
            'version' => $this->version,
            'name' => $this->name,
            'description' => $this->description,
            'recommended_scope' => $this->recommendedScope->value,
            'audience' => $this->audience,
            'risk' => $this->risk,
            'permissions' => $this->permissions,
            'deprecated' => $this->deprecated,
            'replacement_key' => $this->replacementKey,
        ];
        if ($this->type !== null) {
            $entry['type'] = $this->type->value;
        }
        if ($this->subjectPopulation !== null) {
            $entry['subject_population'] = $this->subjectPopulation->value;
        }

        return $entry;
    }
}
