<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

use InvalidArgumentException;

/**
 * Any subset of a closed set of translated options.
 *
 * Unlike a sequence of independent yes/no decisions, one multiple-choice reply
 * preserves the set the person reviewed as a single value. The host can render
 * the options as a checkbox list, offer select-all and deselect-all actions,
 * and still send only values declared by the owning App.
 */
final readonly class MultipleChoiceDecision extends ImportDecision
{
    public const TYPE = 'multiple_choice';

    /**
     * @param  list<ChoiceOption>  $options
     * @param  list<string>  $defaultValues
     * @param  array<string, string|int>  $labelParams
     */
    public function __construct(
        string $key,
        string $labelKey,
        public array $options,
        public array $defaultValues = [],
        array $labelParams = [],
        bool $blocking = false,
    ) {
        if ($options === []) {
            throw new InvalidArgumentException('An import multiple-choice decision requires at least one option.');
        }

        $optionValues = array_map(static fn (ChoiceOption $option): string => $option->value, $options);
        if (count($optionValues) !== count(array_unique($optionValues))) {
            throw new InvalidArgumentException('An import multiple-choice decision requires unique option values.');
        }
        if (array_filter($defaultValues, static fn (mixed $value): bool => ! is_string($value)) !== []
            || count($defaultValues) !== count(array_unique($defaultValues))
            || array_diff($defaultValues, $optionValues) !== []) {
            throw new InvalidArgumentException('An import multiple-choice decision default must contain unique offered values.');
        }

        parent::__construct($key, $labelKey, $labelParams, $blocking);
    }

    public function type(): string
    {
        return self::TYPE;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => self::TYPE,
            'key' => $this->key,
            'label_key' => $this->labelKey,
            'label_params' => $this->labelParams,
            'blocking' => $this->blocking,
            'default_values' => $this->defaultValues,
            'options' => array_map(
                static fn (ChoiceOption $option): array => $option->toArray(),
                $this->options,
            ),
        ];
    }
}
