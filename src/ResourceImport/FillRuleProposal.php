<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

/**
 * A proposed value for a required field no vendor file carries.
 *
 * Declared by the App that owns the resource, because these are domain policy
 * rather than anything readable from the file. A card statement carries four of
 * the eleven fields the card pipeline requires; the other seven describe where the
 * file came from, what currency the books are kept in, and how a foreign amount was
 * converted. None of that is in the spreadsheet, and none of it is the host's to
 * decide.
 *
 * Three kinds, distinguished because they behave differently per row:
 *
 * - **fixed** — one value for the whole file (`transaction_kind`, `source_key`).
 * - **policy** — a domain decision that holds only under a stated condition. A
 *   1.0 exchange rate is right for a statement whose currency column is entirely
 *   KRW and wrong otherwise, so the condition is declared and checked against the
 *   file rather than assumed.
 * - **derived** — follows another column, evaluated per row. A fixed
 *   `exchange_rate_at` would stamp all 312 rows with one date; `sameAs` keeps each
 *   row's own.
 *
 * Every proposal carries the i18n key for *why*. Seven pre-filled boxes with no
 * stated basis are not reviewable — the user either accepts them blindly or
 * abandons the import.
 */
final class FillRuleProposal
{
    /**
     * @param  string  $key  the schema key this fills
     * @param  scalar|null  $value  the proposed value; null when `sameAs` is set
     * @param  string|null  $sameAs  another schema key this follows, per row
     * @param  string|null  $basisKey  i18n key stating why this value is proposed
     * @param  array{key: string, equals: string}|null  $requiresUniformColumn
     *         the proposal applies only when every value in the named schema key's
     *         mapped column equals this. The host checks it against the file, so a
     *         policy that does not hold is withheld rather than pre-filled wrongly.
     * @param  string  $group  proposals sharing a group are confirmed together, so
     *         the FX block reads as one decision instead of five questions
     */
    public function __construct(
        public readonly string $key,
        public readonly string|int|float|bool|null $value = null,
        public readonly ?string $sameAs = null,
        public readonly ?string $basisKey = null,
        public readonly ?array $requiresUniformColumn = null,
        public readonly string $group = 'general',
    ) {}

    /** A value that follows another column has to be resolved per row, not merged once. */
    public function isDerived(): bool
    {
        return $this->sameAs !== null && $this->sameAs !== '';
    }

    /**
     * The rule as a mapping spec constant.
     *
     * @return array<string, string>|scalar
     */
    public function toRule(): array|string|int|float|bool
    {
        return $this->isDerived() ? ['same_as' => (string) $this->sameAs] : ($this->value ?? '');
    }
}
