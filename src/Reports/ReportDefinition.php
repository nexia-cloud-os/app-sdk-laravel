<?php

declare(strict_types=1);

namespace Nexia\Reports;

use InvalidArgumentException;
use Nexia\ResourceComposition\CompositionSpec;

/** An immutable App report template; saving changes creates a user-owned report. */
final readonly class ReportDefinition
{
    public function toArray(): array
    {
        return ['key' => $this->key, 'title_key' => $this->titleKey,
            'composition_spec' => $this->composition->toArray(), 'presentation_spec' => $this->presentation];
    }

    public static function fromArray(array $definition): self
    {
        return new self($definition['key'], $definition['title_key'],
            CompositionSpec::fromArray($definition['composition_spec']), $definition['presentation_spec']);
    }

    /** A resource count still uses the same authorized Composition path as any other report. */
    public static function count(string $key, string $titleKey, string $resourceKey): self
    {
        $all = ['kind' => 'all', 'children' => []];

        return new self($key, $titleKey, CompositionSpec::fromArray([
            'schema_version' => 1,
            'sources' => [['alias' => 'record', 'resource_key' => $resourceKey]],
            'relationships' => [], 'population' => ['mode' => 'anchor', 'source' => 'record'],
            'fields' => [], 'timezone' => 'UTC', 'where' => $all, 'dimensions' => [],
            'measures' => [['alias' => 'records', 'source' => 'record', 'field' => null,
                'operation' => 'count', 'result_type' => 'integer', 'null_behavior' => 'zero',
                'unit' => 'count', 'where' => $all]],
            'having' => $all, 'derived' => [], 'comparison' => null, 'sort' => [], 'limit' => null,
            'result' => ['shape' => 'aggregate'],
        ]), ['component' => 'MetricCard', 'channels' => ['value' => 'records']]);
    }

    /** @param array<string, mixed> $presentation */
    public function __construct(
        public string $key,
        public string $titleKey,
        public CompositionSpec $composition,
        public array $presentation,
    ) {
        if (preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9_.-]*\z/D', $key) !== 1) {
            throw new InvalidArgumentException('Report key must be an App-namespaced identifier.');
        }
        if ($titleKey === '' || trim($titleKey) !== $titleKey) {
            throw new InvalidArgumentException('Report titleKey must be a non-empty catalog key.');
        }
        if (! in_array($presentation['component'] ?? null, ['Table', 'MetricCard', 'Chart', 'PivotTable'], true)) {
            throw new InvalidArgumentException('Report presentation must use a Composition renderer.');
        }
    }
}
