<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use InvalidArgumentException;
use ValueError;

/** Declared constraints for selecting `directory.party` references. */
final readonly class PartySelectionConstraint
{
    /** @param non-empty-list<PartySelectionType> $types */
    public function __construct(
        public array $types,
        public PartySelectionEligibility $eligibility,
    ) {
        if (! array_is_list($types) || $types === []) {
            throw new InvalidArgumentException('Party selection types must be a non-empty list.');
        }

        $values = [];
        foreach ($types as $type) {
            if (! $type instanceof PartySelectionType || isset($values[$type->value])) {
                throw new InvalidArgumentException('Party selection types must be unique typed values.');
            }
            $values[$type->value] = true;
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        if (array_is_list($data)
            || count($data) !== 2
            || ! array_key_exists('types', $data)
            || ! array_key_exists('eligibility', $data)
            || ! is_array($data['types'])
            || ! array_is_list($data['types'])
            || ! is_string($data['eligibility'])) {
            throw new InvalidArgumentException('Party selection must contain only types and eligibility.');
        }

        try {
            return new self(
                types: array_map(
                    static fn (mixed $type): PartySelectionType => is_string($type)
                        ? PartySelectionType::from($type)
                        : throw new InvalidArgumentException('Party selection types must be strings.'),
                    $data['types'],
                ),
                eligibility: PartySelectionEligibility::from($data['eligibility']),
            );
        } catch (ValueError $exception) {
            throw new InvalidArgumentException('Party selection contains an unsupported value.', previous: $exception);
        }
    }

    /** @return array{types: non-empty-list<string>, eligibility: string} */
    public function toArray(): array
    {
        return [
            'types' => array_map(
                static fn (PartySelectionType $type): string => $type->value,
                $this->types,
            ),
            'eligibility' => $this->eligibility->value,
        ];
    }
}
