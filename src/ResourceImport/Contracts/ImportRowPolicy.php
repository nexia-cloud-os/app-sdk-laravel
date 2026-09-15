<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

/**
 * Which rows this import refuses, and why.
 *
 * Not the same as the schema's required fields, and that difference is the
 * whole reason the App decides it. A personnel row cannot be placed without an
 * employment category, because an employment needs one and no rule can guess
 * it; the same row is perfectly importable without an email, because a worker
 * without an account is a legitimate state — a contractor, or a hire IT has not
 * provisioned yet.
 *
 * The host can see that a column is empty. Only the App knows whether that
 * makes the row unplaceable.
 *
 * Answered from the file alone, before anything is written. The refusals a
 * write produces are a different set arriving by a different route.
 */
interface ImportRowPolicy
{
    /**
     * @param  list<array<string, mixed>>  $rows  the host's mapped output, keyed by
     *         schema key, each carrying `__line` for the spreadsheet row number
     * @return list<array{row: int, fields: list<string>}>  the rows refused and the
     *         columns that caused each refusal. Naming the columns matters: "row
     *         14 was rejected" sends someone hunting, and "row 14 has no
     *         employment category" does not
     */
    public function rejected(array $rows): array;
}
