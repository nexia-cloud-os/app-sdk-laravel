<?php

declare(strict_types=1);

namespace Nexia\ResourceComposition;

/**
 * Strict, presentation-free intent for one bounded read-only Resource composition.
 *
 * Resource existence, field visibility, operator availability, relationship
 * truth, and SQL authorization completeness remain live host decisions. This
 * DTO validates only the stable wire structure and cross-field invariants.
 */
final readonly class CompositionSpec
{
    public const SCHEMA_VERSION = 1;

    public const SCHEMA_VERSION_V2 = 2;

    public const SCHEMA_VERSION_V3 = 3;

    public const SCHEMA_VERSION_V4 = 4;

    /** @var list<string> */
    private const FILTER_OPERATORS = ['eq', 'in'];

    /** @var list<string> */
    private const DIMENSION_BUCKETS = ['day', 'week', 'month'];

    /** @var list<string> */
    private const MEASURE_OPERATIONS = ['count', 'distinct_count', 'sum', 'avg'];

    /** @var list<string> */
    private const MEASURE_RESULT_TYPES = ['integer', 'decimal'];

    /** @var list<string> */
    private const MEASURE_NULL_BEHAVIORS = ['zero', 'null_if_empty'];

    /** @var list<string> */
    private const RESULT_SHAPES = ['rows', 'aggregate'];

    private const MAX_V4_NODES = 100;

    private const MAX_V4_LIST_ITEMS = 100;

    private const MAX_V4_STRING_LENGTH = 512;

    private const ALIAS_PATTERN = '/\A[a-z][a-z0-9_]{0,31}\z/D';

    private const FIELD_PATTERN = '/\A[a-z][a-z0-9_]{0,159}\z/D';

    private const RELATIONSHIP_KEY_PATTERN = '/\A[a-z][a-z0-9_.-]{0,159}\z/D';

    private const RESOURCE_KEY_PATTERN = '/\A[a-z][a-z0-9]*(?:[-_][a-z0-9]+)*\.[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)*\z/D';

    /**
     * @param  list<array{alias: string, resource_key: string}>  $sources
     * @param  array<string, string>|null  $relationship
     * @param  list<array{key: string, source?: string, target?: string}>  $relationships
     * @param  list<array{source: string, field: string}>  $fields
     * @param  list<array{source: string, field: string, operator: string, value: scalar|list<scalar>}>  $filters
     * @param  list<array{source: string, field: string, bucket: string|null}>  $dimensions
     * @param  list<array{alias: string, source: string, field: string|null, operation: string, result_type: string, null_behavior: string, unit: string}>  $measures
     * @param  array{shape: string}  $result
     */
    private function __construct(
        public int $schemaVersion,
        public array $sources,
        public ?array $relationship,
        public array $relationships,
        public array $fields,
        public array $filters,
        public array $dimensions,
        public array $measures,
        public array $result,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $schemaVersion = $input['schema_version'] ?? null;
        if (! in_array($schemaVersion, [self::SCHEMA_VERSION, self::SCHEMA_VERSION_V2, self::SCHEMA_VERSION_V3, self::SCHEMA_VERSION_V4], true)) {
            self::fail(
                'unsupported_schema_version',
                'schema_version',
                'Resource Composition schema_version must be 1, 2, 3, or 4.',
            );
        }

        if ($schemaVersion === self::SCHEMA_VERSION_V4) {
            return self::fromV4($input);
        }

        self::assertKeys(
            $input,
            $schemaVersion === self::SCHEMA_VERSION ? [
                'schema_version',
                'sources',
                'relationship',
                'fields',
                'filters',
                'dimensions',
                'measures',
                'result',
            ] : [
                'schema_version',
                'sources',
                'relationships',
                'fields',
                'filters',
                'dimensions',
                'measures',
                'result',
            ],
            '$',
        );

        $sourceValues = self::list($input['sources'] ?? null, 'sources');
        if (($schemaVersion === self::SCHEMA_VERSION && (count($sourceValues) < 1 || count($sourceValues) > 2))
            || ($schemaVersion === self::SCHEMA_VERSION_V2 && count($sourceValues) !== 3)
            || ($schemaVersion === self::SCHEMA_VERSION_V3 && count($sourceValues) < 2)) {
            self::fail(
                'invalid_source_count',
                'sources',
                $schemaVersion === self::SCHEMA_VERSION
                    ? 'Resource Composition schema v1 requires one or two sources.'
                    : ($schemaVersion === self::SCHEMA_VERSION_V2
                        ? 'Resource Composition schema v2 requires exactly three sources.'
                        : 'Resource Composition schema v3 requires at least two sources.'),
            );
        }
        if ($schemaVersion === self::SCHEMA_VERSION) {
            self::assertRelationshipCount(count($sourceValues), $input['relationship'] ?? null);
        }

        $sources = self::sources($sourceValues, $schemaVersion === self::SCHEMA_VERSION_V3);
        $aliases = array_column($sources, 'alias');
        $relationship = $schemaVersion === self::SCHEMA_VERSION
            ? self::relationship($input['relationship'] ?? null)
            : null;
        $relationships = match ($schemaVersion) {
            self::SCHEMA_VERSION_V2 => self::relationships($input['relationships'] ?? null),
            self::SCHEMA_VERSION_V3 => self::graphRelationships($input['relationships'] ?? null, $aliases),
            default => [],
        };
        $fields = self::fields($input['fields'] ?? null, $aliases);
        $filters = self::filters($input['filters'] ?? null, $aliases);
        $dimensions = self::dimensions($input['dimensions'] ?? null, $aliases);
        $measures = self::measures($input['measures'] ?? null, $aliases);
        $result = self::result($input['result'] ?? null);

        self::assertResultShape($result['shape'], $fields, $dimensions, $measures);

        return new self(
            schemaVersion: $schemaVersion,
            sources: $sources,
            relationship: $relationship,
            relationships: $relationships,
            fields: $fields,
            filters: $filters,
            dimensions: $dimensions,
            measures: $measures,
            result: $result,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->schemaVersion === self::SCHEMA_VERSION_V4) {
            return [
                'schema_version' => 4,
                'sources' => $this->sources,
                'relationships' => $this->relationships,
                'fields' => $this->fields,
                'timezone' => $this->result['timezone'],
                'where' => $this->filters,
                'dimensions' => $this->dimensions,
                'measures' => $this->measures,
                'having' => $this->result['having'],
                'derived' => $this->result['derived'],
                'comparison' => $this->result['comparison'],
                'sort' => $this->result['sort'],
                'limit' => $this->result['limit'],
                'result' => ['shape' => $this->result['shape']],
            ];
        }
        return [
            'schema_version' => $this->schemaVersion,
            'sources' => $this->sources,
            ...($this->schemaVersion === self::SCHEMA_VERSION
                ? ['relationship' => $this->relationship]
                : ['relationships' => $this->relationships]),
            'fields' => $this->fields,
            'filters' => $this->filters,
            'dimensions' => $this->dimensions,
            'measures' => $this->measures,
            'result' => $this->result,
        ];
    }

    /** @param array<string,mixed> $input */
    private static function fromV4(array $input): self
    {
        self::assertKeys($input, ['schema_version', 'sources', 'relationships', 'fields', 'timezone', 'where', 'dimensions', 'measures', 'having', 'derived', 'comparison', 'sort', 'limit', 'result'], '$');
        $budget = ['nodes' => 0];
        $sources = self::sources(self::v4List($input['sources'], 'sources'), true);
        if ($sources === []) self::fail('invalid_source_count', 'sources', 'Resource Composition schema v4 requires at least one source.');
        $aliases = array_column($sources, 'alias');
        $relationships = self::v4List($input['relationships'], 'relationships');
        if (count($sources) === 1 && $relationships !== []) self::fail('relationship_not_allowed', 'relationships', 'Single-source Resource Composition cannot declare relationships.');
        if (count($sources) > 1) $relationships = self::graphRelationships($relationships, $aliases);
        $timezone = self::timezone($input['timezone'], 'timezone');
        $where = self::v4Predicate($input['where'], 'where', $aliases, $budget);
        $fields = self::v4Fields($input['fields'], $aliases, $budget);
        $dimensions = self::v4Dimensions($input['dimensions'], $aliases, $budget);
        $measures = self::v4Measures($input['measures'], $aliases, $budget);
        $derived = self::v4Derived($input['derived'], $aliases, array_column($measures, 'alias'), $budget);
        $rowDerived = array_column(array_values(array_filter($derived, static fn (array $item): bool => $item['stage'] === 'row')), 'alias');
        foreach ($fields as $index => $field) {
            if (isset($field['derived']) && ! in_array($field['derived'], $rowDerived, true)) self::fail('invalid_identifier', "fields.{$index}.derived", 'Row derived field is unavailable.');
        }
        foreach ($dimensions as $index => $dimension) {
            if (($dimension['target']['kind'] ?? null) === 'derived' && ! in_array($dimension['target']['alias'], $rowDerived, true)) self::fail('invalid_identifier', "dimensions.{$index}.target.alias", 'Row derived dimension is unavailable.');
        }
        foreach ($measures as $index => $measure) {
            if (($measure['field']['kind'] ?? null) === 'derived' && ! in_array($measure['field']['alias'], $rowDerived, true)) self::fail('invalid_identifier', "measures.{$index}.field.alias", 'Row derived measure field is unavailable.');
        }
        self::v4ValidateDerivedReferences($derived);
        $aggregateDerived = array_column(array_values(array_filter($derived, static fn (array $item): bool => $item['stage'] === 'aggregate')), 'alias');
        $having = self::v4Having($input['having'], 'having', array_column($measures, 'alias'), $aggregateDerived, $budget);
        $comparison = self::v4Comparison($input['comparison'], $aliases, array_column($measures, 'alias'), $aggregateDerived, $budget);
        $sort = self::v4Sort($input['sort'], $aliases, array_column($measures, 'alias'), array_column($derived, 'alias'), $budget);
        $limit = self::v4Limit($input['limit']);
        $result = self::result($input['result']);
        if ($result['shape'] === 'rows') {
            if ($fields === [] || $dimensions !== [] || $measures !== [] || $having !== ['kind' => 'all', 'children' => []] || $comparison !== null || array_filter($derived, static fn (array $item): bool => $item['stage'] !== 'row') !== []) self::fail('result_shape_mismatch', 'result.shape', 'Row results cannot declare aggregate constructs.');
            foreach ($sort as $item) if (($item['target']['kind'] ?? null) !== 'field' && ($item['target']['kind'] ?? null) !== 'derived') self::fail('result_shape_mismatch', 'sort', 'Row results sort only by fields or row derived values.');
        } elseif ($fields !== [] || $measures === []) {
            self::fail('result_shape_mismatch', 'result.shape', 'Aggregate results require measures and cannot declare row fields.');
        }
        return new self(4, $sources, null, $relationships, $fields, $where, $dimensions, $measures, ['shape' => $result['shape'], 'timezone' => $timezone, 'having' => $having, 'derived' => $derived, 'comparison' => $comparison, 'sort' => $sort, 'limit' => $limit]);
    }

    /** @param list<string> $aliases @param array{nodes:int} $budget @return list<array<string,mixed>> */
    private static function v4Fields(mixed $value, array $aliases, array &$budget): array
    {
        $fields = [];
        foreach (self::v4List($value, 'fields') as $index => $item) {
            self::v4Node($budget, "fields.{$index}");
            $path = "fields.{$index}";
            $field = self::object($item, $path);
            if (array_key_exists('derived', $field)) {
                self::assertKeys($field, ['derived'], $path);
                $fields[] = ['derived' => self::alias($field['derived'], "{$path}.derived")];
                continue;
            }
            self::assertKeys($field, ['source', 'field'], $path);
            $fields[] = ['source' => self::source($field['source'], $aliases, "{$path}.source"), 'field' => self::field($field['field'], "{$path}.field")];
        }

        return $fields;
    }
    /** @param list<string> $aliases @return list<array<string,mixed>> */
    private static function v4Dimensions(mixed $value, array $aliases, array &$budget): array
    {
        $dimensions = [];
        foreach (self::v4List($value, 'dimensions') as $index => $item) {
            self::v4Node($budget, "dimensions.{$index}");
            $path = "dimensions.{$index}";
            $dimension = self::object($item, $path);
            self::assertKeys($dimension, ['target', 'bucket'], $path);
            $bucket = $dimension['bucket'] === null ? null : self::choice($dimension['bucket'], ['day', 'week', 'month', 'quarter', 'year'], "{$path}.bucket", 'invalid_shape');
            $dimensions[] = ['target' => self::v4Target($dimension['target'], "{$path}.target", $aliases), 'bucket' => $bucket];
        }

        return $dimensions;
    }
    /** @param list<string> $aliases @return list<array<string,mixed>> */
    private static function v4Measures(mixed $value, array $aliases, array &$budget): array
    {
        $measures = [];
        $seen = [];
        foreach (self::v4List($value, 'measures') as $index => $item) {
            self::v4Node($budget, "measures.{$index}");
            $path = "measures.{$index}";
            $measure = self::object($item, $path);
            self::assertKeys($measure, ['alias', 'source', 'field', 'operation', 'result_type', 'null_behavior', 'unit', 'where'], $path);
            $alias = self::alias($measure['alias'], "{$path}.alias");
            self::assertUniqueTuple($seen, $alias, "{$path}.alias");
            $operation = self::choice($measure['operation'], ['count', 'distinct_count', 'sum', 'avg', 'min', 'max', 'median'], "{$path}.operation", 'invalid_measure');
            $field = $measure['field'];
            $resultType = self::choice($measure['result_type'], ['integer', 'decimal'], "{$path}.result_type", 'invalid_measure');
            $nullBehavior = self::choice($measure['null_behavior'], ['zero', 'null_if_empty'], "{$path}.null_behavior", 'invalid_measure');
            $unit = self::unit($measure['unit'], "{$path}.unit");
            if ($operation === 'count' && $field !== null) {
                self::fail('invalid_measure', "{$path}.field", 'Count cannot have a field.');
            }
            if (in_array($operation, ['count', 'distinct_count'], true)
                && ($resultType !== 'integer' || $nullBehavior !== 'zero' || $unit !== 'count')) {
                self::fail('invalid_measure', $path, 'Count measures require integer result, zero null behavior, and count unit.');
            }
            if ($operation !== 'count') {
                $field = self::v4Target($field, "{$path}.field", $aliases);
            }
            $measures[] = [
                'alias' => $alias,
                'source' => self::source($measure['source'], $aliases, "{$path}.source"),
                'field' => $field,
                'operation' => $operation,
                'result_type' => $resultType,
                'null_behavior' => $nullBehavior,
                'unit' => $unit,
                'where' => self::v4Predicate($measure['where'], "{$path}.where", $aliases, $budget),
            ];
        }

        return $measures;
    }

    /** @param list<string> $aliases @return array<string,mixed> */
    private static function v4Target(mixed $value, string $path, array $aliases): array
    {
        $target = self::object($value, $path);
        $kind = self::choice($target['kind'] ?? null, ['field', 'derived'], "{$path}.kind", 'invalid_shape');
        if ($kind === 'field') {
            self::assertKeys($target, ['kind', 'source', 'field'], $path);
            return ['kind' => 'field', 'source' => self::source($target['source'], $aliases, "{$path}.source"), 'field' => self::field($target['field'], "{$path}.field")];
        }
        self::assertKeys($target, ['kind', 'alias'], $path);
        return ['kind' => 'derived', 'alias' => self::alias($target['alias'], "{$path}.alias")];
    }
    /** @param list<string> $aliases @return array<string,mixed> */
    private static function v4Predicate(mixed $value, string $path, array $aliases, array &$budget, int $depth = 0): array
    {
        if ($depth > 8) {
            self::fail('invalid_shape', $path, 'Resource Composition predicate nesting is too deep.');
        }

        self::v4Node($budget, $path);
        $predicate = self::object($value, $path);
        $kind = self::choice($predicate['kind'] ?? null, ['all', 'any', 'not', 'condition', 'relative_date'], "{$path}.kind", 'invalid_shape');

        if ($kind === 'all' || $kind === 'any') {
            self::assertKeys($predicate, ['kind', 'children'], $path);
            $children = [];
            foreach (self::v4List($predicate['children'], "{$path}.children") as $index => $child) {
                $children[] = self::v4Predicate($child, "{$path}.children.{$index}", $aliases, $budget, $depth + 1);
            }

            return ['kind' => $kind, 'children' => $children];
        }

        if ($kind === 'not') {
            self::assertKeys($predicate, ['kind', 'child'], $path);

            return ['kind' => 'not', 'child' => self::v4Predicate($predicate['child'], "{$path}.child", $aliases, $budget, $depth + 1)];
        }

        if ($kind === 'relative_date') {
            self::assertKeys($predicate, ['kind', 'target', 'period'], $path);

            return [
                'kind' => 'relative_date',
                'target' => self::v4Target($predicate['target'], "{$path}.target", $aliases),
                'period' => self::choice($predicate['period'], ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_quarter', 'last_quarter', 'this_year', 'last_year'], "{$path}.period", 'invalid_filter_value'),
            ];
        }

        self::assertKeys($predicate, ['kind', 'target', 'operator', 'value'], $path);
        $operator = self::choice($predicate['operator'], ['eq', 'in', 'neq', 'not_in', 'gt', 'gte', 'lt', 'lte', 'between', 'is_null', 'not_null', 'contains', 'not_contains', 'starts_with', 'ends_with'], "{$path}.operator", 'invalid_filter_value');
        $filterValue = $predicate['value'];

        if (in_array($operator, ['is_null', 'not_null'], true)) {
            if ($filterValue !== null) {
                self::fail('invalid_filter_value', "{$path}.value", 'Null predicates require null value.');
            }
        } elseif (in_array($operator, ['in', 'not_in'], true)) {
            $filterValue = self::v4ScalarList($filterValue, "{$path}.value");
        } elseif ($operator === 'between') {
            $filterValue = self::object($filterValue, "{$path}.value");
            self::assertKeys($filterValue, ['from', 'to'], "{$path}.value");
            if (! self::isV4Scalar($filterValue['from']) || ! self::isV4Scalar($filterValue['to'])) {
                self::fail('invalid_filter_value', "{$path}.value", 'Between requires scalar bounds.');
            }
        } elseif (! self::isV4Scalar($filterValue)) {
            self::fail('invalid_filter_value', "{$path}.value", 'Predicate requires scalar value.');
        }

        return ['kind' => 'condition', 'target' => self::v4Target($predicate['target'], "{$path}.target", $aliases), 'operator' => $operator, 'value' => $filterValue];
    }
    /** @param list<string> $measures @param list<string> $derived @return array<string,mixed> */
    private static function v4Having(mixed $value, string $path, array $measures, array $derived, array &$budget, int $depth = 0): array
    {
        if ($depth > 8) {
            self::fail('invalid_shape', $path, 'Resource Composition predicate nesting is too deep.');
        }

        self::v4Node($budget, $path);
        $predicate = self::object($value, $path);
        $kind = self::choice($predicate['kind'] ?? null, ['all', 'any', 'not', 'condition'], "{$path}.kind", 'invalid_shape');

        if ($kind === 'all' || $kind === 'any') {
            self::assertKeys($predicate, ['kind', 'children'], $path);
            $children = [];
            foreach (self::v4List($predicate['children'], "{$path}.children") as $index => $child) {
                $children[] = self::v4Having($child, "{$path}.children.{$index}", $measures, $derived, $budget, $depth + 1);
            }

            return ['kind' => $kind, 'children' => $children];
        }

        if ($kind === 'not') {
            self::assertKeys($predicate, ['kind', 'child'], $path);

            return ['kind' => 'not', 'child' => self::v4Having($predicate['child'], "{$path}.child", $measures, $derived, $budget, $depth + 1)];
        }

        self::assertKeys($predicate, ['kind', 'target', 'operator', 'value'], $path);
        $target = self::object($predicate['target'], "{$path}.target");
        self::assertKeys($target, ['kind', 'alias'], "{$path}.target");
        $targetKind = self::choice($target['kind'], ['measure', 'derived'], "{$path}.target.kind", 'invalid_shape');
        $alias = self::alias($target['alias'], "{$path}.target.alias");
        if (! in_array($alias, $targetKind === 'measure' ? $measures : $derived, true)) {
            self::fail('invalid_identifier', "{$path}.target.alias", 'Aggregate target is unavailable.');
        }

        $operator = self::choice($predicate['operator'], ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'between', 'is_null', 'not_null'], "{$path}.operator", 'invalid_filter_value');
        $filterValue = $predicate['value'];
        $invalidValue = in_array($operator, ['is_null', 'not_null'], true)
            ? $filterValue !== null
            : ($operator === 'between'
                ? ! (is_array($filterValue) && self::isWireScalar($filterValue['from'] ?? null) && self::isWireScalar($filterValue['to'] ?? null))
                : ! self::isWireScalar($filterValue));
        if ($invalidValue) {
            self::fail('invalid_filter_value', "{$path}.value", 'Aggregate predicate value is invalid.');
        }

        return ['kind' => 'condition', 'target' => ['kind' => $targetKind, 'alias' => $alias], 'operator' => $operator, 'value' => $filterValue];
    }

    /** @param list<string> $aliases @param list<string> $measures @return list<array<string,mixed>> */
    private static function v4Derived(mixed $value, array $aliases, array $measures, array &$budget): array
    {
        $derived = [];
        $seen = [];
        foreach (self::v4List($value, 'derived') as $index => $value) {
            self::v4Node($budget, "derived.{$index}");
            $path = "derived.{$index}";
            $entry = self::object($value, $path);
            $stage = self::choice($entry['stage'] ?? null, ['row', 'aggregate'], "{$path}.stage", 'invalid_shape');
            self::assertKeys($entry, $stage === 'row'
                ? ['alias', 'stage', 'source', 'result_type', 'unit', 'null_behavior', 'expression']
                : ['alias', 'stage', 'result_type', 'unit', 'expression'], $path);

            $alias = self::alias($entry['alias'], "{$path}.alias");
            self::assertUniqueTuple($seen, $alias, "{$path}.alias");
            $parsed = ['alias' => $alias, 'stage' => $stage];
            if ($stage === 'row') {
                $parsed['source'] = self::source($entry['source'], $aliases, "{$path}.source");
            }
            $parsed['result_type'] = self::choice($entry['result_type'], ['integer', 'decimal', 'number', 'date', 'datetime', 'string'], "{$path}.result_type", 'invalid_shape');
            $parsed['unit'] = $entry['unit'] === null ? null : self::unit($entry['unit'], "{$path}.unit");
            if ($stage === 'row') {
                $parsed['null_behavior'] = self::choice($entry['null_behavior'], ['null_if_empty'], "{$path}.null_behavior", 'invalid_shape');
            }
            $parsed['expression'] = self::v4Expression($entry['expression'], "{$path}.expression", $aliases, $measures, $stage, $budget);
            $derived[] = $parsed;
        }

        return $derived;
    }
    /** @param list<string> $aliases @param list<string> $measures @return array<string,mixed> */
    private static function v4Expression(mixed $value, string $path, array $aliases, array $measures, string $stage, array &$budget, int $depth = 0): array
    {
        if ($depth > 8) {
            self::fail('invalid_shape', $path, 'Resource Composition expression nesting is too deep.');
        }

        self::v4Node($budget, $path);
        $expression = self::object($value, $path);
        $allowed = $stage === 'row'
            ? ['field', 'derived', 'literal', 'coalesce', 'date_diff_days', 'case', 'add', 'subtract', 'multiply', 'divide']
            : ['measure', 'derived', 'literal', 'add', 'subtract', 'multiply', 'divide'];
        $kind = self::choice($expression['kind'] ?? null, $allowed, "{$path}.kind", 'invalid_shape');

        if ($kind === 'field') {
            self::assertKeys($expression, ['kind', 'source', 'field'], $path);

            return ['kind' => 'field', 'source' => self::source($expression['source'], $aliases, "{$path}.source"), 'field' => self::field($expression['field'], "{$path}.field")];
        }
        if ($kind === 'measure' || $kind === 'derived') {
            self::assertKeys($expression, ['kind', 'alias'], $path);
            $alias = self::alias($expression['alias'], "{$path}.alias");
            if ($kind === 'measure' && ! in_array($alias, $measures, true)) {
                self::fail('invalid_identifier', "{$path}.alias", 'Measure is unavailable.');
            }

            return ['kind' => $kind, 'alias' => $alias];
        }
        if ($kind === 'literal') {
            self::assertKeys($expression, ['kind', 'literal_type', 'value'], $path);
            $literalType = self::choice($expression['literal_type'], ['number', 'string', 'boolean', 'null'], "{$path}.literal_type", 'invalid_shape');
            $literal = $expression['value'];
            $invalidLiteral = ($literalType === 'null' && $literal !== null)
                || ($literalType === 'number' && (! is_int($literal) && ! is_float($literal) || is_float($literal) && ! is_finite($literal)))
                || ($literalType === 'string' && (! is_string($literal) || mb_strlen($literal) > self::MAX_V4_STRING_LENGTH))
                || ($literalType === 'boolean' && ! is_bool($literal));
            if ($invalidLiteral) {
                self::fail('invalid_shape', "{$path}.value", 'Literal type does not match value.');
            }

            return ['kind' => 'literal', 'literal_type' => $literalType, 'value' => $literal];
        }
        if ($kind === 'coalesce') {
            self::assertKeys($expression, ['kind', 'values'], $path);
            $values = [];
            foreach (self::v4List($expression['values'], "{$path}.values") as $index => $item) {
                $values[] = self::v4Expression($item, "{$path}.values.{$index}", $aliases, $measures, $stage, $budget, $depth + 1);
            }

            return ['kind' => 'coalesce', 'values' => $values];
        }
        if ($kind === 'date_diff_days') {
            self::assertKeys($expression, ['kind', 'start', 'end'], $path);

            return ['kind' => $kind, 'start' => self::v4Expression($expression['start'], "{$path}.start", $aliases, $measures, $stage, $budget, $depth + 1), 'end' => self::v4Expression($expression['end'], "{$path}.end", $aliases, $measures, $stage, $budget, $depth + 1)];
        }
        if ($kind === 'case') {
            self::assertKeys($expression, ['kind', 'branches', 'otherwise'], $path);
            $branches = [];
            foreach (self::v4List($expression['branches'], "{$path}.branches") as $index => $branch) {
                $branchPath = "{$path}.branches.{$index}";
                $branch = self::object($branch, $branchPath);
                self::assertKeys($branch, ['when', 'then'], $branchPath);
                $branches[] = ['when' => self::v4Predicate($branch['when'], "{$branchPath}.when", $aliases, $budget, $depth + 1), 'then' => self::v4Expression($branch['then'], "{$branchPath}.then", $aliases, $measures, $stage, $budget, $depth + 1)];
            }

            return ['kind' => 'case', 'branches' => $branches, 'otherwise' => self::v4Expression($expression['otherwise'], "{$path}.otherwise", $aliases, $measures, $stage, $budget, $depth + 1)];
        }

        self::assertKeys($expression, ['kind', 'left', 'right'], $path);

        return ['kind' => $kind, 'left' => self::v4Expression($expression['left'], "{$path}.left", $aliases, $measures, $stage, $budget, $depth + 1), 'right' => self::v4Expression($expression['right'], "{$path}.right", $aliases, $measures, $stage, $budget, $depth + 1)];
    }
    private static function v4Comparison(mixed $value, array $aliases, array $measures, array $derived, array &$budget): ?array
    {
        if ($value === null) return null;
        self::v4Node($budget, 'comparison');
        $comparison = self::object($value, 'comparison');
        self::assertKeys($comparison, ['kind', 'dimension', 'targets'], 'comparison');
        $dimension = self::object($comparison['dimension'], 'comparison.dimension');
        self::assertKeys($dimension, ['source', 'field', 'bucket'], 'comparison.dimension');
        $targets = [];
        foreach (self::v4List($comparison['targets'], 'comparison.targets') as $index => $item) {
            $path = "comparison.targets.{$index}";
            $target = self::object($item, $path);
            self::assertKeys($target, ['kind', 'alias'], $path);
            $kind = self::choice($target['kind'], ['measure', 'derived'], "{$path}.kind", 'invalid_shape');
            $alias = self::alias($target['alias'], "{$path}.alias");
            if (! in_array($alias, $kind === 'measure' ? $measures : $derived, true)) self::fail('invalid_identifier', "{$path}.alias", 'Comparison target is unavailable.');
            $targets[] = ['kind' => $kind, 'alias' => $alias];
        }
        if ($targets === []) self::fail('invalid_shape', 'comparison.targets', 'Comparison requires targets.');
        return ['kind' => self::choice($comparison['kind'], ['previous_period', 'previous_year'], 'comparison.kind', 'invalid_shape'), 'dimension' => ['source' => self::source($dimension['source'], $aliases, 'comparison.dimension.source'), 'field' => self::field($dimension['field'], 'comparison.dimension.field'), 'bucket' => self::choice($dimension['bucket'], ['day', 'week', 'month', 'quarter', 'year'], 'comparison.dimension.bucket', 'invalid_shape')], 'targets' => $targets];
    }

    private static function v4Sort(mixed $value, array $aliases, array $measures, array $derived, array &$budget): array
    {
        $sort = [];
        foreach (self::v4List($value, 'sort') as $index => $item) {
            self::v4Node($budget, "sort.{$index}");
            $path = "sort.{$index}";
            $entry = self::object($item, $path);
            self::assertKeys($entry, ['target', 'direction', 'nulls'], $path);
            $target = self::object($entry['target'], "{$path}.target");
            $kind = self::choice($target['kind'] ?? null, ['field', 'dimension', 'measure', 'derived'], "{$path}.target.kind", 'invalid_shape');
            if (in_array($kind, ['field', 'dimension'], true)) {
                self::assertKeys($target, ['kind', 'source', 'field'], "{$path}.target");
                $parsedTarget = ['kind' => $kind, 'source' => self::source($target['source'], $aliases, "{$path}.target.source"), 'field' => self::field($target['field'], "{$path}.target.field")];
            } else {
                self::assertKeys($target, ['kind', 'alias'], "{$path}.target");
                $alias = self::alias($target['alias'], "{$path}.target.alias");
                if (! in_array($alias, $kind === 'measure' ? $measures : $derived, true)) self::fail('invalid_identifier', "{$path}.target.alias", 'Sort target is unavailable.');
                $parsedTarget = ['kind' => $kind, 'alias' => $alias];
            }
            $sort[] = ['target' => $parsedTarget, 'direction' => self::choice($entry['direction'], ['asc', 'desc'], "{$path}.direction", 'invalid_shape'), 'nulls' => self::choice($entry['nulls'], ['first', 'last'], "{$path}.nulls", 'invalid_shape')];
        }

        return $sort;
    }

    private static function v4Limit(mixed $value): ?array
    {
        if ($value === null) return null;
        $limit = self::object($value, 'limit');
        self::assertKeys($limit, ['count'], 'limit');
        if (! is_int($limit['count']) || $limit['count'] < 1 || $limit['count'] > 1000) self::fail('invalid_shape', 'limit.count', 'Limit is invalid.');
        return ['count' => $limit['count']];
    }

    private static function timezone(mixed $value, string $path): string
    {
        if (! is_string($value) || ! in_array($value, timezone_identifiers_list(), true)) self::fail('invalid_shape', $path, 'Timezone is invalid.');
        return $value;
    }

    /** @return list<mixed> */
    private static function v4List(mixed $value, string $path): array
    {
        $items = self::list($value, $path);
        if (count($items) > self::MAX_V4_LIST_ITEMS) self::fail('invalid_shape', $path, 'Resource Composition list is too large.');

        return $items;
    }

    /** @return list<scalar> */
    private static function v4ScalarList(mixed $value, string $path): array
    {
        $items = self::v4List($value, $path);
        if ($items === []) self::fail('invalid_filter_value', $path, 'The in filter requires a non-empty scalar list.');
        foreach ($items as $item) if (! self::isV4Scalar($item)) self::fail('invalid_filter_value', $path, 'The in filter requires a scalar list.');

        return $items;
    }

    /** @param array{nodes:int} $budget */
    private static function v4Node(array &$budget, string $path): void
    {
        if (++$budget['nodes'] > self::MAX_V4_NODES) self::fail('invalid_shape', $path, 'Resource Composition is too complex.');
    }

    /** @param list<array<string,mixed>> $derived */
    private static function v4ValidateDerivedReferences(array $derived): void
    {
        $stages = [];
        foreach ($derived as $item) $stages[$item['alias']] = $item['stage'];
        $walk = static function (array $expression, string $stage, array &$references) use (&$walk): void {
            if (($expression['kind'] ?? null) === 'derived') $references[] = $expression['alias'];
            foreach (['left', 'right', 'start', 'end', 'otherwise'] as $key) if (isset($expression[$key])) $walk($expression[$key], $stage, $references);
            foreach (($expression['values'] ?? []) as $value) $walk($value, $stage, $references);
            foreach (($expression['branches'] ?? []) as $branch) $walk($branch['then'], $stage, $references);
        };
        $graph = [];
        foreach ($derived as $item) {
            $references = []; $walk($item['expression'], $item['stage'], $references);
            foreach ($references as $alias) if (! isset($stages[$alias]) || $stages[$alias] !== $item['stage']) self::fail('invalid_identifier', 'derived', 'Derived expression references an unavailable stage.');
            $graph[$item['alias']] = $references;
        }
        $visiting = []; $visited = [];
        $visit = static function (string $alias) use (&$visit, &$graph, &$visiting, &$visited): void {
            if (isset($visiting[$alias])) self::fail('invalid_identifier', 'derived', 'Derived expressions cannot form a cycle.');
            if (isset($visited[$alias])) return;
            $visiting[$alias] = true; foreach ($graph[$alias] as $reference) $visit($reference); unset($visiting[$alias]); $visited[$alias] = true;
        };
        foreach (array_keys($graph) as $alias) $visit($alias);
    }

    /** @param list<mixed> $items @return list<array{alias: string, resource_key: string}> */
    private static function sources(array $items, bool $allowDuplicateResourceKeys): array
    {
        $sources = [];
        $aliases = [];
        $resourceKeys = [];
        foreach ($items as $index => $value) {
            $path = "sources.{$index}";
            $source = self::object($value, $path);
            self::assertKeys($source, ['alias', 'resource_key'], $path);
            $alias = self::alias($source['alias'] ?? null, "{$path}.alias");
            $resourceKey = self::resourceKey($source['resource_key'] ?? null, "{$path}.resource_key");

            if (isset($aliases[$alias]) || (! $allowDuplicateResourceKeys && isset($resourceKeys[$resourceKey]))) {
                self::fail(
                    'duplicate_identifier',
                    $path,
                    $allowDuplicateResourceKeys
                        ? 'Resource Composition source aliases must be unique.'
                        : 'Resource Composition source aliases and Resource keys must be unique.',
                );
            }
            $aliases[$alias] = true;
            $resourceKeys[$resourceKey] = true;
            $sources[] = ['alias' => $alias, 'resource_key' => $resourceKey];
        }

        return $sources;
    }

    /** @return array{key: string}|null */
    private static function relationship(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $relationship = self::object($value, 'relationship');
        self::assertKeys($relationship, ['key'], 'relationship');

        return [
            'key' => self::relationshipKey($relationship['key'] ?? null, 'relationship.key'),
        ];
    }

    /** @return list<array{key: string}> */
    private static function relationships(mixed $value): array
    {
        $values = self::list($value, 'relationships');
        if (count($values) !== 2) {
            self::fail(
                'relationship_required',
                'relationships',
                'Resource Composition schema v2 requires exactly two relationships.',
            );
        }

        $relationships = [];
        $seen = [];
        foreach ($values as $index => $value) {
            $path = "relationships.{$index}";
            $relationship = self::object($value, $path);
            self::assertKeys($relationship, ['key'], $path);
            $key = self::relationshipKey($relationship['key'] ?? null, "{$path}.key");
            self::assertUniqueTuple($seen, $key, "{$path}.key");
            $relationships[] = ['key' => $key];
        }

        return $relationships;
    }

    /** @param list<string> $aliases @return list<array{key: string, source: string, target: string}> */
    private static function graphRelationships(mixed $value, array $aliases): array
    {
        $values = self::list($value, 'relationships');
        if ($values === []) {
            self::fail(
                'relationship_required',
                'relationships',
                'Resource Composition schema v3 requires at least one relationship.',
            );
        }

        $relationships = [];
        $seen = [];
        foreach ($values as $index => $value) {
            $path = "relationships.{$index}";
            $relationship = self::object($value, $path);
            self::assertKeys($relationship, ['key', 'source', 'target'], $path);
            $key = self::relationshipKey($relationship['key'] ?? null, "{$path}.key");
            $source = self::source($relationship['source'] ?? null, $aliases, "{$path}.source");
            $target = self::source($relationship['target'] ?? null, $aliases, "{$path}.target");
            self::assertUniqueTuple($seen, "{$key}\0{$source}\0{$target}", $path);
            $relationships[] = compact('key', 'source', 'target');
        }

        return $relationships;
    }

    /** @param list<string> $aliases @return list<array{source: string, field: string}> */
    private static function fields(mixed $value, array $aliases): array
    {
        $fields = [];
        $seen = [];
        foreach (self::list($value, 'fields') as $index => $value) {
            $path = "fields.{$index}";
            $item = self::object($value, $path);
            self::assertKeys($item, ['source', 'field'], $path);
            $source = self::source($item['source'] ?? null, $aliases, "{$path}.source");
            $field = self::field($item['field'] ?? null, "{$path}.field");
            self::assertUniqueTuple($seen, "{$source}.{$field}", $path);
            $fields[] = ['source' => $source, 'field' => $field];
        }

        return $fields;
    }

    /** @param list<string> $aliases @return list<array{source: string, field: string, operator: string, value: scalar|list<scalar>}> */
    private static function filters(mixed $value, array $aliases): array
    {
        $filters = [];
        foreach (self::list($value, 'filters') as $index => $value) {
            $path = "filters.{$index}";
            $item = self::object($value, $path);
            self::assertKeys($item, ['source', 'field', 'operator', 'value'], $path);
            $operator = $item['operator'] ?? null;
            if (! is_string($operator) || ! in_array($operator, self::FILTER_OPERATORS, true)) {
                self::fail(
                    'invalid_filter_value',
                    "{$path}.operator",
                    'Resource Composition filter operator is invalid.',
                );
            }

            $filterValue = $item['value'] ?? null;
            if ($operator === 'in') {
                $filterValue = self::scalarList($filterValue, "{$path}.value");
            } elseif (! self::isWireScalar($filterValue)) {
                self::fail(
                    'invalid_filter_value',
                    "{$path}.value",
                    'The eq filter value must be a scalar.',
                );
            }

            $filters[] = [
                'source' => self::source($item['source'] ?? null, $aliases, "{$path}.source"),
                'field' => self::field($item['field'] ?? null, "{$path}.field"),
                'operator' => $operator,
                'value' => $filterValue,
            ];
        }

        return $filters;
    }

    /** @param list<string> $aliases @return list<array{source: string, field: string, bucket: string|null}> */
    private static function dimensions(mixed $value, array $aliases): array
    {
        $dimensions = [];
        $seen = [];
        foreach (self::list($value, 'dimensions') as $index => $value) {
            $path = "dimensions.{$index}";
            $item = self::object($value, $path);
            self::assertKeys($item, ['source', 'field', 'bucket'], $path);
            $source = self::source($item['source'] ?? null, $aliases, "{$path}.source");
            $field = self::field($item['field'] ?? null, "{$path}.field");
            $bucketValue = $item['bucket'] ?? null;
            if ($bucketValue !== null
                && (! is_string($bucketValue) || ! in_array($bucketValue, self::DIMENSION_BUCKETS, true))) {
                self::fail(
                    'invalid_shape',
                    "{$path}.bucket",
                    'Resource Composition dimension bucket is invalid.',
                );
            }
            self::assertUniqueTuple($seen, "{$source}.{$field}", $path);
            $dimensions[] = [
                'source' => $source,
                'field' => $field,
                'bucket' => $bucketValue,
            ];
        }

        return $dimensions;
    }

    /** @param list<string> $aliases @return list<array{alias: string, source: string, field: string|null, operation: string, result_type: string, null_behavior: string, unit: string}> */
    private static function measures(mixed $value, array $aliases): array
    {
        $measures = [];
        $seen = [];
        foreach (self::list($value, 'measures') as $index => $value) {
            $path = "measures.{$index}";
            $item = self::object($value, $path);
            self::assertKeys(
                $item,
                ['alias', 'source', 'field', 'operation', 'result_type', 'null_behavior', 'unit'],
                $path,
            );
            $alias = self::alias($item['alias'] ?? null, "{$path}.alias");
            self::assertUniqueTuple($seen, $alias, "{$path}.alias");
            $source = self::source($item['source'] ?? null, $aliases, "{$path}.source");
            $operation = self::choice(
                $item['operation'] ?? null,
                self::MEASURE_OPERATIONS,
                "{$path}.operation",
                'invalid_measure',
            );
            $resultType = self::choice(
                $item['result_type'] ?? null,
                self::MEASURE_RESULT_TYPES,
                "{$path}.result_type",
                'invalid_measure',
            );
            $nullBehavior = self::choice(
                $item['null_behavior'] ?? null,
                self::MEASURE_NULL_BEHAVIORS,
                "{$path}.null_behavior",
                'invalid_measure',
            );
            $unit = self::unit($item['unit'] ?? null, "{$path}.unit");
            $field = $item['field'] ?? null;

            if ($operation === 'count') {
                if ($field !== null
                    || $resultType !== 'integer'
                    || $nullBehavior !== 'zero'
                    || $unit !== 'count') {
                    self::fail(
                        'invalid_measure',
                        $path,
                        'Count measures require a null field, integer result, zero null behavior, and count unit.',
                    );
                }
            } elseif ($operation === 'distinct_count') {
                $field = self::field($field, "{$path}.field");
                if ($resultType !== 'integer'
                    || $nullBehavior !== 'zero'
                    || $unit !== 'count') {
                    self::fail(
                        'invalid_measure',
                        $path,
                        'Distinct count measures require a field, integer result, zero null behavior, and count unit.',
                    );
                }
            } else {
                $field = self::field($field, "{$path}.field");
                if ($resultType !== 'decimal'
                    || $nullBehavior !== 'null_if_empty'
                    || $unit === 'count') {
                    self::fail(
                        'invalid_measure',
                        $path,
                        'Sum and avg measures require a field, decimal result, null_if_empty behavior, and a declared unit.',
                    );
                }
            }

            $measures[] = [
                'alias' => $alias,
                'source' => $source,
                'field' => $field,
                'operation' => $operation,
                'result_type' => $resultType,
                'null_behavior' => $nullBehavior,
                'unit' => $unit,
            ];
        }

        return $measures;
    }

    /** @return array{shape: string} */
    private static function result(mixed $value): array
    {
        $result = self::object($value, 'result');
        self::assertKeys($result, ['shape'], 'result');
        $shape = $result['shape'] ?? null;
        if (! is_string($shape) || ! in_array($shape, self::RESULT_SHAPES, true)) {
            self::fail(
                'invalid_shape',
                'result.shape',
                'Resource Composition result shape is invalid.',
            );
        }

        return ['shape' => $shape];
    }

    private static function assertRelationshipCount(int $sourceCount, mixed $relationship): void
    {
        if ($sourceCount === 2 && $relationship === null) {
            self::fail(
                'relationship_required',
                'relationship',
                'Two-source Resource Composition requires one relationship.',
            );
        }
        if ($sourceCount === 1 && $relationship !== null) {
            self::fail(
                'relationship_not_allowed',
                'relationship',
                'Single-source Resource Composition cannot declare a relationship.',
            );
        }
    }

    /**
     * @param  list<array{source: string, field: string}>  $fields
     * @param  list<array{source: string, field: string, bucket: string|null}>  $dimensions
     * @param  list<array{alias: string, source: string, field: string|null, operation: string, result_type: string, null_behavior: string, unit: string}>  $measures
     */
    private static function assertResultShape(
        string $shape,
        array $fields,
        array $dimensions,
        array $measures,
    ): void {
        if ($shape === 'rows') {
            if ($fields === [] || $dimensions !== [] || $measures !== []) {
                self::fail(
                    'result_shape_mismatch',
                    'result.shape',
                    'Row results require fields and cannot declare dimensions or measures.',
                );
            }

            return;
        }

        if ($fields !== [] || $measures === []) {
            self::fail(
                'result_shape_mismatch',
                'result.shape',
                'Aggregate results require measures and cannot declare row fields.',
            );
        }
    }

    /** @param array<string, mixed> $value @param list<string> $keys */
    private static function assertKeys(array $value, array $keys, string $path): void
    {
        foreach ($value as $key => $_) {
            if (! is_string($key) || ! in_array($key, $keys, true)) {
                $name = is_string($key) ? $key : (string) $key;
                self::fail(
                    'unknown_key',
                    $path === '$' ? $name : "{$path}.{$name}",
                    'Resource Composition contains an unknown key.',
                );
            }
        }
        foreach ($keys as $key) {
            if (! array_key_exists($key, $value)) {
                self::fail(
                    'invalid_shape',
                    $path === '$' ? $key : "{$path}.{$key}",
                    'Resource Composition is missing a required key.',
                );
            }
        }
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $path): array
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            self::fail(
                'invalid_shape',
                $path,
                'Resource Composition value must be an object.',
            );
        }

        return $value;
    }

    /** @return list<mixed> */
    private static function list(mixed $value, string $path): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            self::fail(
                'invalid_shape',
                $path,
                'Resource Composition value must be a list.',
            );
        }

        return $value;
    }

    private static function alias(mixed $value, string $path): string
    {
        if (! is_string($value) || preg_match(self::ALIAS_PATTERN, $value) !== 1) {
            self::fail(
                'invalid_identifier',
                $path,
                'Resource Composition alias is invalid.',
            );
        }

        return $value;
    }

    private static function field(mixed $value, string $path): string
    {
        if (! is_string($value) || preg_match(self::FIELD_PATTERN, $value) !== 1) {
            self::fail(
                'invalid_identifier',
                $path,
                'Resource Composition field is invalid.',
            );
        }

        return $value;
    }

    private static function relationshipKey(mixed $value, string $path): string
    {
        if (! is_string($value) || preg_match(self::RELATIONSHIP_KEY_PATTERN, $value) !== 1) {
            self::fail(
                'invalid_identifier',
                $path,
                'Resource Composition relationship key is invalid.',
            );
        }

        return $value;
    }

    private static function resourceKey(mixed $value, string $path): string
    {
        if (! is_string($value)
            || strlen($value) > 160
            || preg_match(self::RESOURCE_KEY_PATTERN, $value) !== 1) {
            self::fail(
                'invalid_identifier',
                $path,
                'Resource Composition Resource key is invalid.',
            );
        }

        return $value;
    }

    /** @param list<string> $choices */
    private static function choice(mixed $value, array $choices, string $path, string $code): string
    {
        if (! is_string($value) || ! in_array($value, $choices, true)) {
            self::fail($code, $path, 'Resource Composition value is invalid.');
        }

        return $value;
    }

    /** @param list<string> $aliases */
    private static function source(mixed $value, array $aliases, string $path): string
    {
        $source = self::alias($value, $path);
        self::assertKnownSource($source, $aliases, $path);

        return $source;
    }

    /** @param list<string> $aliases */
    private static function assertKnownSource(string $source, array $aliases, string $path): void
    {
        if (! in_array($source, $aliases, true)) {
            self::fail(
                'unknown_source_alias',
                $path,
                'Resource Composition source alias is not declared.',
            );
        }
    }

    /** @param array<string, true> $seen */
    private static function assertUniqueTuple(array &$seen, string $key, string $path): void
    {
        if (isset($seen[$key])) {
            self::fail(
                'duplicate_identifier',
                $path,
                'Resource Composition entries must be unique.',
            );
        }
        $seen[$key] = true;
    }

    /** @return non-empty-list<scalar> */
    private static function scalarList(mixed $value, string $path): array
    {
        $values = self::list($value, $path);
        if ($values === []) {
            self::fail(
                'invalid_filter_value',
                $path,
                'The in filter requires a non-empty scalar list.',
            );
        }
        foreach ($values as $candidate) {
            if (! self::isWireScalar($candidate)) {
                self::fail(
                    'invalid_filter_value',
                    $path,
                    'The in filter requires a non-empty scalar list.',
                );
            }
        }

        return $values;
    }

    private static function isV4Scalar(mixed $value): bool
    {
        return (is_string($value) && mb_strlen($value) <= self::MAX_V4_STRING_LENGTH)
            || is_int($value)
            || is_bool($value)
            || (is_float($value) && is_finite($value));
    }

    private static function isWireScalar(mixed $value): bool
    {
        return is_string($value)
            || is_int($value)
            || is_bool($value)
            || (is_float($value) && is_finite($value));
    }

    private static function unit(mixed $value, string $path): string
    {
        if (! is_string($value)
            || $value !== trim($value)
            || strlen($value) > 64
            || preg_match('/\A[A-Za-z][A-Za-z0-9._-]*\z/D', $value) !== 1) {
            self::fail(
                'invalid_measure',
                $path,
                'Resource Composition measure unit is invalid.',
            );
        }

        return $value;
    }

    private static function fail(string $code, string $path, string $message): never
    {
        throw new CompositionSpecValidationException($code, $path, $message);
    }
}
