<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Plan;

/**
 * A group of lines the card draws together under one heading.
 *
 * Grouping is the App's judgement rather than the host's, because what belongs
 * beside what depends on the domain. A personnel plan separates the structure a
 * file creates from the people it employs; a card statement has one group and
 * needs no heading at all.
 */
final readonly class ImportPlanSection
{
    /**
     * @param  string|null  $headingKey  i18n key for the heading, or null for a
     *         group that needs none
     * @param  list<ImportPlanItem>  $items
     * @param  array<string, string|int>  $headingParams
     */
    public function __construct(
        public array $items,
        public ?string $headingKey = null,
        public array $headingParams = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'heading_key' => $this->headingKey,
            'heading_params' => $this->headingParams,
            'items' => array_map(
                static fn (ImportPlanItem $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }
}
