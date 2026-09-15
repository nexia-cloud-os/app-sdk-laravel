<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\Signature\SignatureParticipantAssignmentSource;

/** Assignment authorities one template binding permits for a signatory role. */
final readonly class SignatureParticipantAssignmentPolicy
{
    /** @var non-empty-list<SignatureParticipantAssignmentSource> */
    public array $allowedSources;

    /** @param non-empty-list<SignatureParticipantAssignmentSource> $allowedSources */
    public function __construct(array $allowedSources)
    {
        if (! array_is_list($allowedSources) || $allowedSources === []) {
            throw new InvalidArgumentException('Signature participant assignment policy requires assignment sources.');
        }

        $indexed = [];
        foreach ($allowedSources as $source) {
            if (! $source instanceof SignatureParticipantAssignmentSource || isset($indexed[$source->value])) {
                throw new InvalidArgumentException('Signature participant assignment policy sources must be unique SDK enum values.');
            }
            $indexed[$source->value] = $source;
        }
        ksort($indexed, SORT_STRING);
        $this->allowedSources = array_values($indexed);
    }

    /** @return array{allowed_sources:non-empty-list<string>} */
    public function toArray(): array
    {
        return [
            'allowed_sources' => array_map(
                static fn (SignatureParticipantAssignmentSource $source): string => $source->value,
                $this->allowedSources,
            ),
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        if (array_is_list($data)
            || array_keys($data) !== ['allowed_sources']
            || ! is_array($data['allowed_sources'])
            || ! array_is_list($data['allowed_sources'])) {
            throw new InvalidArgumentException('Serialized signature participant assignment policy shape is invalid.');
        }

        return new self(array_map(
            static fn (mixed $source): SignatureParticipantAssignmentSource => is_string($source)
                ? SignatureParticipantAssignmentSource::from($source)
                : throw new InvalidArgumentException('Serialized signature participant assignment sources must be strings.'),
            $data['allowed_sources'],
        ));
    }
}
