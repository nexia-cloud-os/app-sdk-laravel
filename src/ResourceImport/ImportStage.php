<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

/**
 * Normalized pipeline stage.
 *
 * Apps disagree about the words. One family spells the apply-eligible state
 * `VALIDATING` and its terminal state `COMPLETED`; another spells them
 * `VALIDATED` and `APPLIED`. Neither name is wrong, and neither can be inferred:
 * `VALIDATING` reads like work in progress but is assigned at the *end* of the
 * validation transaction, and it is what the app's own `apply()` gates on.
 *
 * So the host never interprets an app's state name. An adapter declares which of
 * its states are apply-eligible and maps them onto these values.
 *
 * `AwaitingApply` is named for the property the host depends on — approval and
 * apply may now proceed — rather than for what already happened, because that is
 * the only thing a caller needs to decide from it.
 */
enum ImportStage: string
{
    /** Batch exists, rows not yet validated. */
    case Received = 'received';

    /** Rows are frozen and verified; approval and apply may proceed. */
    case AwaitingApply = 'awaiting_apply';

    /** Rows were committed. Terminal. */
    case Applied = 'applied';

    /** Validation accepted nothing usable. Terminal unless the app allows retry. */
    case Rejected = 'rejected';

    /**
     * Something failed. Not necessarily terminal — at least one app treats a
     * failed batch as apply-eligible so a retry can finish it, which is why
     * apply-eligibility is declared per adapter instead of derived from stage.
     */
    case Failed = 'failed';
}
