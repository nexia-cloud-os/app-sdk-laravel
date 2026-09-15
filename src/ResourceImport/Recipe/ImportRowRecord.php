<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Recipe;

/**
 * One record a file creates **per row**, and where its values come from.
 *
 * The distinction from {@see ImportPrerequisite} is not organisational. A
 * prerequisite is named by a *value* — forty rows saying `재무팀` mean one
 * department — so it is decided once per distinct value. A row record is named
 * by the row itself: three hundred rows are three hundred workers, and matching
 * them against each other would be wrong.
 *
 * The order inside a row is not derived. It is a chain, and each link needs the
 * one before it, so these are stated in the order they must be created. The
 * order *between* prerequisites is derived, because that one can be.
 */
final readonly class ImportRowRecord
{
    /**
     * @param  string  $resourceKey  the resource this row creates one of
     * @param  array<string, string>  $attributes  attribute key → file column
     * @param  array<string, mixed>  $constants  attributes every row gets
     * @param  array<string, ImportRef>  $refs  ref column → where it comes from
     * @param  ImportRef|null  $preferInstead  a reference to use in place of
     *         creating this record, when it resolves. A row with a work email
     *         already has a person identity through the account; creating a
     *         second would give one person two identities and leave nothing able
     *         to say which the payslip belongs to
     * @param  ImportDeferredRef|null  $deferred  a reference resolved on a second
     *         pass, once every record in the run exists
     */
    public function __construct(
        public string $resourceKey,
        public array $attributes = [],
        public array $constants = [],
        public array $refs = [],
        public ?ImportRef $preferInstead = null,
        public ?ImportDeferredRef $deferred = null,
    ) {}
}
