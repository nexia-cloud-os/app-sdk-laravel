<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\Signature\SignatureBulkParticipantAssignmentSource;

/** Assignment sources one binding permits for a specific signatory role. */
final readonly class SignatureBulkRoleAssignmentPolicy
{
    /** @var non-empty-list<SignatureBulkParticipantAssignmentSource> */
    public array $allowedSources;

    /** @param non-empty-list<SignatureBulkParticipantAssignmentSource> $allowedSources */
    public function __construct(
        public string $roleKey,
        array $allowedSources,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1 || strlen($roleKey) > 100) {
            throw new InvalidArgumentException('Signature bulk role assignment policy role_key must be lower snake-case.');
        }
        if (! array_is_list($allowedSources) || $allowedSources === []) {
            throw new InvalidArgumentException('Signature bulk role assignment policy requires assignment sources.');
        }

        $indexed = [];
        foreach ($allowedSources as $source) {
            if (! $source instanceof SignatureBulkParticipantAssignmentSource || isset($indexed[$source->value])) {
                throw new InvalidArgumentException('Signature bulk role assignment policy sources must be unique SDK enum values.');
            }
            $indexed[$source->value] = $source;
        }
        ksort($indexed, SORT_STRING);
        $this->allowedSources = array_values($indexed);
    }

    /** @return array{role_key:string,allowed_sources:non-empty-list<string>} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'allowed_sources' => array_map(
                static fn (SignatureBulkParticipantAssignmentSource $source): string => $source->value,
                $this->allowedSources,
            ),
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $keys = ['role_key', 'allowed_sources'];
        if (array_is_list($data)
            || array_diff(array_keys($data), $keys) !== []
            || array_diff($keys, array_keys($data)) !== []
            || ! is_string($data['role_key'] ?? null)
            || ! is_array($data['allowed_sources'] ?? null)
            || ! array_is_list($data['allowed_sources'])) {
            throw new InvalidArgumentException('Serialized signature bulk role assignment policy shape is invalid.');
        }

        return new self(
            roleKey: $data['role_key'],
            allowedSources: array_map(
                static fn (mixed $source): SignatureBulkParticipantAssignmentSource => is_string($source)
                    ? SignatureBulkParticipantAssignmentSource::from($source)
                    : throw new InvalidArgumentException('Serialized signature bulk assignment sources must be strings.'),
                $data['allowed_sources'],
            ),
        );
    }
}
