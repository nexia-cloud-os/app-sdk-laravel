<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/** App-owned authorized query plus the only semantic aliases Core may use. */
final readonly class AuthorizedCompositionQuery
{
    /**
     * Fields may declare required_dimensions: list<string> of same-source field
     * keys. Value aggregates require these exact unbucketed dimensions or a
     * single-value equality constraint. Count and distinct_count are exempt.
     * Core enforces this for direct and derived measures, including comparisons.
     *
     * @param  array<string,array<string,mixed>>  $fields
     */
    public function __construct(
        public Builder $query,
        public array $fields,
        public string $grainAlias = 'public_id',
    ) {
        $this->assertTemporalIntervals();
    }

    private function assertTemporalIntervals(): void
    {
        foreach ($this->fields as $fieldKey => $field) {
            $interval = is_array($field) ? ($field['temporal_interval'] ?? null) : null;
            if ($interval === null) {
                continue;
            }

            $start = is_array($interval) ? ($interval['start_field'] ?? null) : null;
            $end = is_array($interval) ? ($interval['end_field'] ?? null) : null;
            $startField = is_string($start) ? ($this->fields[$start] ?? null) : null;
            $endField = is_string($end) ? ($this->fields[$end] ?? null) : null;
            if (! is_string($fieldKey)
                || ! is_array($field)
                || ($field['type'] ?? null) !== 'resource_reference'
                || ! is_string($field['reference_resource_key'] ?? null)
                || ! is_array($interval)
                || array_diff(array_keys($interval), ['start_field', 'end_field', 'end_bound', 'null_start', 'null_end']) !== []
                || ! is_string($start)
                || ! is_string($end)
                || ! is_array($startField)
                || ! is_array($endField)
                || ($startField['type'] ?? null) !== 'date'
                || ($endField['type'] ?? null) !== 'date'
                || ($interval['end_bound'] ?? null) !== 'exclusive'
                || ($interval['null_start'] ?? null) !== 'reject'
                || ($interval['null_end'] ?? null) !== 'open') {
                throw new InvalidArgumentException('Temporal interval metadata must bind a Resource Reference subject to direct date fields with [start, end) semantics.');
            }
        }
    }
}
