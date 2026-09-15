<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Recipe;

/**
 * What a file means, stated as declarations, in terms only the App knows.
 *
 * The host can derive a great deal from a file and a schema — which headings
 * map where, which rows are complete, what order records have to be created in.
 * What it cannot derive is which column of *this* file names which resource,
 * because that depends on the vendor rather than on either side's model. This
 * is that half, and it is the App's to write.
 *
 * Nothing here says what order to create prerequisites in. The host derives it
 * from the relations between the resources named, intersected with the columns
 * the file populates. Row records are different — the chain inside one row is
 * not derivable and is stated in order.
 *
 * The whole point of the type is what it *cannot* express. There is no model
 * class, no action class, no permission slug and no host service. A recipe is
 * data about a file, and an App that can only hand over data cannot reach past
 * the gates the host puts in front of the work.
 */
final readonly class ImportRecipe
{
    /**
     * @param  array<string, ImportPrerequisite>  $prerequisites  spec key → record
     *                                                            decided once per distinct value
     * @param  array<string, ImportRowRecord>  $rows  spec key → record created per
     *                                                row, in the order they have to be created
     * @param  array<string, list<string>>  $mappedColumns  resource key → nullable
     *                                                      ref columns this file populates, so the host promotes them to
     *                                                      required when deriving the order.
     *
     *         Without it, jobs and grades do not have to precede assignments —
     *         the columns allow null — and the derived order is wrong. It is
     *         stated rather than inferred from `$rows` because a column may be
     *         populated by a prerequisite the row only references
     * @param  array<string, string>  $planLabelKeys  resource key → App-owned
     *                                                i18n key for the plan line that counts that resource. The host owns
     *                                                layout and numbers; the App owns the domain noun
     */
    public function __construct(
        public array $prerequisites = [],
        public array $rows = [],
        public array $mappedColumns = [],
        public array $planLabelKeys = [],
    ) {}

    /**
     * Every resource key this recipe will have the host create.
     *
     * The host asks before running anything, so a recipe naming a resource its
     * pipeline may not create is refused as a contract error rather than
     * discovered halfway through a transaction.
     *
     * @return list<string>
     */
    public function resourceKeys(): array
    {
        $keys = [];

        foreach ($this->prerequisites as $prerequisite) {
            $keys[$prerequisite->resourceKey] = true;
        }

        foreach ($this->rows as $row) {
            $keys[$row->resourceKey] = true;
        }

        return array_keys($keys);
    }

    /**
     * Every source column this recipe deliberately reads.
     *
     * The host compares this list with the contributed TransferSchema so a
     * template can never advertise a column that the commit silently ignores.
     *
     * @return list<string>
     */
    public function consumedColumns(): array
    {
        $columns = [];

        foreach ($this->prerequisites as $prerequisite) {
            foreach ($prerequisite->columns() as $column) {
                $columns[$column] = true;
            }
            foreach ($prerequisite->attributesFrom as $column) {
                $columns[$column] = true;
            }
        }

        foreach ($this->rows as $row) {
            foreach ($row->attributes as $column) {
                $columns[$column] = true;
            }
            if ($row->deferred !== null) {
                $columns[$row->deferred->column] = true;
            }
        }

        $result = array_keys($columns);
        sort($result);

        return $result;
    }
}
