<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Plan;

/**
 * One line of what a batch will bring into being.
 *
 * An i18n key and scalars, never a rendered sentence. The host resolves the key
 * against the owning App's catalog, so the card reads in the user's language
 * rather than in whatever language the App happened to write its plan in — and
 * so an App cannot put markup, a link, or a sentence of its own choosing into a
 * surface the host is vouching for.
 */
final readonly class ImportPlanItem
{
    /**
     * @param  string  $labelKey  i18n key for the line
     * @param  array<string, string|int>  $labelParams  interpolation; scalars only
     * @param  int|null  $count  the number this line is about, when it is about one.
     *         Separate from the params so the host can lay counts out
     *         consistently — right-aligned, thousands-separated in the user's
     *         locale — rather than each App formatting its own
     * @param  bool  $existing  whether this line describes records already present
     *         rather than records to create. "21 departments" and "21
     *         departments, 19 already there" are different promises
     */
    public function __construct(
        public string $labelKey,
        public array $labelParams = [],
        public ?int $count = null,
        public bool $existing = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'label_key' => $this->labelKey,
            'label_params' => $this->labelParams,
            'count' => $this->count,
            'existing' => $this->existing,
        ];
    }
}
