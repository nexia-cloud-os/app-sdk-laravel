<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

/**
 * What this file leaves for a person to settle.
 *
 * Domain judgement, which is why it is not the host's. Ordering 사원 below 과장
 * requires knowing the Korean corporate ladder; knowing that a grade absent
 * from that ladder should fall back to where it first appeared — and that the
 * card must say so — requires knowing why the first rule exists.
 *
 * Proposals rather than questions wherever a basis exists. Asking with empty
 * boxes does not work: seven blanks do not get filled, and the import stalls on
 * fields the App could have answered. So a value arrives pre-filled with its
 * basis stated, and only what genuinely has no proposal blocks.
 *
 * Called with the rows and nothing else. A provider that needed the host's
 * services to decide would be deciding something other than what the file says.
 */
interface ImportDecisionProvider
{
    /**
     * @param  list<array<string, mixed>>  $rows  the host's mapped output, keyed by
     *         schema key, each carrying `__line` for the spreadsheet row number
     * @return list<\Nexia\ResourceImport\Decision\ImportDecision>
     */
    public function propose(array $rows): array;
}
