<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Recipe;

/**
 * A reference resolved on a second pass, once every record in the run exists.
 *
 * A row may point at another row the same file creates later. A manager named
 * on line 12 may be the worker on line 300, so pass one cannot resolve it and
 * pass two can. Resolving in one pass fails for whichever half of the file was
 * written second — which happened, and reported eight managers as missing while
 * they sat in the same upload.
 *
 * Declared rather than inferred, because a deferred reference and an unresolved
 * one look identical after pass one and mean opposite things. What separates
 * them is whether the file was ever going to supply it, and only the recipe
 * knows that.
 */
final readonly class ImportDeferredRef
{
    /**
     * @param  string  $column  the file column holding the printed value
     * @param  string  $against  the row spec whose records it resolves against
     * @param  string  $ref  the ref column to write once it resolves
     * @param  string|null  $acceptanceDecisionKey  the decision that lets a person
     *                                              accept rows whose reference remains unresolved after the host checks
     *                                              both the file and existing records. Null means unresolved entries are
     *                                              blocking issues with no inline acceptance
     * @param  string|null  $acceptanceLabelKey  App-owned i18n key for that decision
     */
    public function __construct(
        public string $column,
        public string $against,
        public string $ref,
        public ?string $acceptanceDecisionKey = null,
        public ?string $acceptanceLabelKey = null,
    ) {
        if (($acceptanceDecisionKey === null) !== ($acceptanceLabelKey === null)) {
            throw new \InvalidArgumentException(
                'A deferred-reference acceptance decision requires both a key and a label key.',
            );
        }

        if ($acceptanceDecisionKey === '' || $acceptanceLabelKey === '') {
            throw new \InvalidArgumentException(
                'Deferred-reference acceptance keys cannot be empty.',
            );
        }
    }
}
