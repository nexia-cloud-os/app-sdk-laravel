<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

/**
 * One named thing needing a number, with the proposal and its basis.
 *
 * `$name` is a value the file printed, not an i18n key — the card shows `과장`
 * because that is what the vendor wrote, and translating it would name
 * something the tenant does not have.
 */
final readonly class KeyedIntegerEntry
{
    /**
     * @param  string  $name  as the file printed it
     * @param  int  $value  the proposal, editable
     * @param  string  $basisKey  i18n key stating where the proposal came from
     * @param  int  $occurrences  how many rows name this, so a reader can weigh it
     */
    public function __construct(
        public string $name,
        public int $value,
        public string $basisKey,
        public int $occurrences = 0,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'basis_key' => $this->basisKey,
            'occurrences' => $this->occurrences,
        ];
    }
}
