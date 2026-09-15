<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

/** One closed, translated option in an import-time human decision. */
final readonly class ChoiceOption
{
    /** @param array<string, string|int> $labelParams */
    public function __construct(
        public string $value,
        public string $labelKey,
        public array $labelParams = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label_key' => $this->labelKey,
            'label_params' => $this->labelParams,
        ];
    }
}
