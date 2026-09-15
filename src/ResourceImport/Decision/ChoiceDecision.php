<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

use InvalidArgumentException;

/** A required selection from a small closed set before an import may apply. */
final readonly class ChoiceDecision extends ImportDecision
{
    public const TYPE = 'choice';

    public const PRESENTATION_SELECT = 'select';

    public const PRESENTATION_CHECKBOX = 'checkbox';

    /**
     * @param  list<ChoiceOption>  $options
     * @param  array<string, string|int>  $labelParams
     */
    public function __construct(
        string $key,
        string $labelKey,
        public array $options,
        array $labelParams = [],
        bool $blocking = true,
        public ?string $defaultValue = null,
        public string $presentation = self::PRESENTATION_SELECT,
    ) {
        if ($options === []) {
            throw new InvalidArgumentException('An import choice decision requires at least one option.');
        }
        if (! in_array($presentation, [self::PRESENTATION_SELECT, self::PRESENTATION_CHECKBOX], true)) {
            throw new InvalidArgumentException('An import choice decision has an unsupported presentation.');
        }
        if ($defaultValue !== null && ! in_array(
            $defaultValue,
            array_map(static fn (ChoiceOption $option): string => $option->value, $options),
            true,
        )) {
            throw new InvalidArgumentException('An import choice decision default must be one of its options.');
        }
        if ($presentation === self::PRESENTATION_CHECKBOX && (count($options) !== 2 || $defaultValue === null)) {
            throw new InvalidArgumentException('A checkbox import choice requires two options and a default value.');
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
            'default_value' => $this->defaultValue,
            'presentation' => $this->presentation,
            'options' => array_map(
                static fn (ChoiceOption $option): array => $option->toArray(),
                $this->options,
            ),
        ];
    }
}
