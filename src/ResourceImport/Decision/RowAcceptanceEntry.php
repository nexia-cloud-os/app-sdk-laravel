<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Decision;

/**
 * One row the import cannot complete, and what it could not complete.
 *
 * `$printed` is the value the file carried, so the card can say *which* manager
 * it could not find rather than only that it could not find one. Shown as text
 * and never resolved into anything — it is by definition a value that matches
 * nothing.
 */
final readonly class RowAcceptanceEntry
{
    /**
     * @param  int  $row  the spreadsheet row number, as the user sees it
     * @param  string  $printed  the value that resolved nowhere
     */
    public function __construct(
        public int $row,
        public string $printed,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'printed' => $this->printed,
        ];
    }
}
