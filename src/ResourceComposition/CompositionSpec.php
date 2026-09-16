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

    private const RESULT_SHAPES = ['rows', 'aggregate'];

    private const MAX_NODES = 100;

    private const MAX_LIST_ITEMS = 100;

    private const MAX_STRING_LENGTH = 512;

    private const ALIAS_PATTERN = '/\A[a-z][a-z0-9_]{0,31}\z/D';

    private const FIELD_PATTERN = '/\A[a-z][a-z0-9_]{0,159}\z/D';

    private const RELATIONSHIP_KEY_PATTERN = '/\A[a-z][a-z0-9_.-]{0,159}\z/D';

    private const RESOURCE_KEY_PATTERN = '/\A[a-z][a-z0-9]*(?:[-_][a-z0-9]+)*\.[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)*\z/D';

    /**
     * @param  list<array{alias: string, resource_key: string}>  $sources
     * @param  list<array{key: string, source: string, target: string, match: string, time_field?: string}>  $relationships
     * @param  list<array<string, mixed>>  $fields
     * @param  array<string, mixed>  $where
     * @param  list<array<string, mixed>>  $dimensions
     * @param  list<array<string, mixed>>  $measures
     * @param  array<string, mixed>  $result
     * @param  array{mode: string, source?: string}  $population
     */
    private function __construct(
        public int $schemaVersion,
        public array $sources,
        public array $relationships,
        public array $fields,
        public array $where,
        public array $dimensions,
        public array $measures,
        public array $result,
        public array $population,
        public ?string $rowSource = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        if (($input['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            self::fail('unsupported_schema_version', 'schema_version', 'Resource Composition schema_version must be 1.');
        }

        $resultInput = self::object($input['result'] ?? null, 'result');
        $shape = $resultInput['shape'] ?? null;
        self::assertKeys($input, $shape === 'rows'
            ? ['schema_version', 'sources', 'relationships', 'population', 'row_source', 'fields', 'timezone', 'where', 'dimensions', 'measures', 'having', 'derived', 'comparison', 'sort', 'limit', 'result']
            : ['schema_version', 'sources', 'relationships', 'population', 'fields', 'timezone', 'where', 'dimensions', 'measures', 'having', 'derived', 'comparison', 'sort', 'limit', 'result'], '$');

        $budget = ['nodes' => 0];
        $sources = self::sources(self::boundedList($input['sources'], 'sources'));
        if ($sources === []) self::fail('invalid_source_count', 'sources', 'Resource Composition requires at least one source.');
        $aliases = array_column($sources, 'alias');
        $relationshipItems = self::list($input['relationships'], 'relationships');
        if (count($sources) === 1 && $relationshipItems !== []) {
            self::fail('relationship_not_allowed', 'relationships', 'Single-source Resource Composition cannot declare relationships.');
        }
        $relationships = self::graphRelationships($relationshipItems, $aliases);
        $timezone = self::timezone($input['timezone'], 'timezone');
        $where = self::predicate($input['where'], 'where', $aliases, $budget);
        $fields = self::fields($input['fields'], $aliases, $budget);
        $dimensions = self::dimensions($input['dimensions'], $aliases, $budget);
        $measures = self::measures($input['measures'], $aliases, $budget);
        $derived = self::derived($input['derived'], $aliases, array_column($measures, 'alias'), $budget);
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
        self::validateDerivedReferences($derived);
        $aggregateDerived = array_column(array_values(array_filter($derived, static fn (array $item): bool => $item['stage'] === 'aggregate')), 'alias');
        $having = self::having($input['having'], 'having', array_column($measures, 'alias'), $aggregateDerived, $budget);
        $comparison = self::comparison($input['comparison'], $aliases, array_column($measures, 'alias'), $aggregateDerived, $budget);
        $sort = self::sort($input['sort'], $aliases, array_column($measures, 'alias'), array_column($derived, 'alias'), $budget);
        $limit = self::limit($input['limit']);
        $result = self::result($input['result']);
        $population = self::population($input['population'], $aliases, $result['shape']);
        $rowSource = $result['shape'] === 'rows' ? self::source($input['row_source'], $aliases, 'row_source') : null;

        if ($result['shape'] === 'rows') {
            if ($fields === [] || $dimensions !== [] || $measures !== [] || $having !== ['kind' => 'all', 'children' => []] || $comparison !== null || array_filter($derived, static fn (array $item): bool => $item['stage'] !== 'row') !== []) self::fail('result_shape_mismatch', 'result.shape', 'Row results cannot declare aggregate constructs.');
            foreach ($sort as $item) if (($item['target']['kind'] ?? null) !== 'field' && ($item['target']['kind'] ?? null) !== 'derived') self::fail('result_shape_mismatch', 'sort', 'Row results sort only by fields or row derived values.');
        } elseif ($fields !== [] || $measures === []) {
            self::fail('result_shape_mismatch', 'result.shape', 'Aggregate results require measures and cannot declare row fields.');
        }

        return new self(self::SCHEMA_VERSION, $sources, $relationships, $fields, $where, $dimensions, $measures, ['shape' => $result['shape'], 'timezone' => $timezone, 'having' => $having, 'derived' => $derived, 'comparison' => $comparison, 'sort' => $sort, 'limit' => $limit], $population, $rowSource);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'sources' => $this->sources,
            'relationships' => $this->relationships,
            'population' => $this->population,
            ...($this->rowSource === null ? [] : ['row_source' => $this->rowSource]),
            'fields' => $this->fields,
            'timezone' => $this->result['timezone'],
            'where' => $this->where,
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

    /** @param list<string> $aliases @param array{nodes:int} $budget @return list<array<string,mixed>> */
    private static function fields(mixed $value, array $aliases, array &$budget): array
    {
        $fields = [];
        $seen = [];
        foreach (self::boundedList($value, 'fields') as $index => $item) {
            self::node($budget, "fields.{$index}");
            $path = "fields.{$index}";
            $field = self::object($item, $path);
            if (array_key_exists('derived', $field)) {
                self::assertKeys($field, ['derived'], $path);
                $derived = self::alias($field['derived'], "{$path}.derived");
                self::assertUniqueTuple($seen, "derived:{$derived}", $path);
                $fields[] = ['derived' => $derived];
                continue;
            }
            self::assertKeys($field, ['source', 'field'], $path);
            $source = self::source($field['source'], $aliases, "{$path}.source");
            $name = self::field($field['field'], "{$path}.field");
            self::assertUniqueTuple($seen, "{$source}.{$name}", $path);
            $fields[] = ['source' => $source, 'field' => $name];
        }

        return $fields;
    }
    /** @param list<string> $aliases @return list<array<string,mixed>> */
    private static function dimensions(mixed $value, array $aliases, array &$budget): array
    {
        $dimensions = [];
        foreach (self::boundedList($value, 'dimensions') as $index => $item) {
            self::node($budget, "dimensions.{$index}");
            $path = "dimensions.{$index}";
            $dimension = self::object($item, $path);
            self::assertKeys($dimension, ['target', 'bucket'], $path);
            $bucket = $dimension['bucket'] === null ? null : self::choice($dimension['bucket'], ['day', 'week', 'month', 'quarter', 'year'], "{$path}.bucket", 'invalid_shape');
            $dimensions[] = ['target' => self::target($dimension['target'], "{$path}.target", $aliases), 'bucket' => $bucket];
        }

        return $dimensions;
    }
    /** @param list<string> $aliases @return list<array<string,mixed>> */
    private static function measures(mixed $value, array $aliases, array &$budget): array
    {
        $measures = [];
        $seen = [];
        foreach (self::boundedList($value, 'measures') as $index => $item) {
            self::node($budget, "measures.{$index}");
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
                $field = self::target($field, "{$path}.field", $aliases);
            }
            $measures[] = [
                'alias' => $alias,
                'source' => self::source($measure['source'], $aliases, "{$path}.source"),
                'field' => $field,
                'operation' => $operation,
                'result_type' => $resultType,
                'null_behavior' => $nullBehavior,
                'unit' => $unit,
                'where' => self::predicate($measure['where'], "{$path}.where", $aliases, $budget),
            ];
        }

        return $measures;
    }

    /** @param list<string> $aliases @return array<string,mixed> */
    private static function target(mixed $value, string $path, array $aliases): array
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
    private static function predicate(mixed $value, string $path, array $aliases, array &$budget, int $depth = 0): array
    {
        if ($depth > 8) {
            self::fail('invalid_shape', $path, 'Resource Composition predicate nesting is too deep.');
        }

        self::node($budget, $path);
        $predicate = self::object($value, $path);
        $kind = self::choice($predicate['kind'] ?? null, ['all', 'any', 'not', 'condition', 'relative_date'], "{$path}.kind", 'invalid_shape');

        if ($kind === 'all' || $kind === 'any') {
            self::assertKeys($predicate, ['kind', 'children'], $path);
            $children = [];
            foreach (self::boundedList($predicate['children'], "{$path}.children") as $index => $child) {
                $children[] = self::predicate($child, "{$path}.children.{$index}", $aliases, $budget, $depth + 1);
            }

            return ['kind' => $kind, 'children' => $children];
        }

        if ($kind === 'not') {
            self::assertKeys($predicate, ['kind', 'child'], $path);

            return ['kind' => 'not', 'child' => self::predicate($predicate['child'], "{$path}.child", $aliases, $budget, $depth + 1)];
        }

        if ($kind === 'relative_date') {
            self::assertKeys($predicate, ['kind', 'target', 'period'], $path);

            return [
                'kind' => 'relative_date',
                'target' => self::target($predicate['target'], "{$path}.target", $aliases),
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
            $filterValue = self::boundedScalarList($filterValue, "{$path}.value");
        } elseif ($operator === 'between') {
            $filterValue = self::object($filterValue, "{$path}.value");
            self::assertKeys($filterValue, ['from', 'to'], "{$path}.value");
            if (! self::isScalar($filterValue['from']) || ! self::isScalar($filterValue['to'])) {
                self::fail('invalid_filter_value', "{$path}.value", 'Between requires scalar bounds.');
            }
        } elseif (! self::isScalar($filterValue)) {
            self::fail('invalid_filter_value', "{$path}.value", 'Predicate requires scalar value.');
        }

        return ['kind' => 'condition', 'target' => self::target($predicate['target'], "{$path}.target", $aliases), 'operator' => $operator, 'value' => $filterValue];
    }
    /** @param list<string> $measures @param list<string> $derived @return array<string,mixed> */
    private static function having(mixed $value, string $path, array $measures, array $derived, array &$budget, int $depth = 0): array
    {
        if ($depth > 8) {
            self::fail('invalid_shape', $path, 'Resource Composition predicate nesting is too deep.');
        }

        self::node($budget, $path);
        $predicate = self::object($value, $path);
        $kind = self::choice($predicate['kind'] ?? null, ['all', 'any', 'not', 'condition'], "{$path}.kind", 'invalid_shape');

        if ($kind === 'all' || $kind === 'any') {
            self::assertKeys($predicate, ['kind', 'children'], $path);
            $children = [];
            foreach (self::boundedList($predicate['children'], "{$path}.children") as $index => $child) {
                $children[] = self::having($child, "{$path}.children.{$index}", $measures, $derived, $budget, $depth + 1);
            }

            return ['kind' => $kind, 'children' => $children];
        }

        if ($kind === 'not') {
            self::assertKeys($predicate, ['kind', 'child'], $path);

            return ['kind' => 'not', 'child' => self::having($predicate['child'], "{$path}.child", $measures, $derived, $budget, $depth + 1)];
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
    private static function derived(mixed $value, array $aliases, array $measures, array &$budget): array
    {
        $derived = [];
        $seen = [];
        foreach (self::boundedList($value, 'derived') as $index => $value) {
            self::node($budget, "derived.{$index}");
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
            $parsed['expression'] = self::expression($entry['expression'], "{$path}.expression", $aliases, $measures, $stage, $budget);
            $derived[] = $parsed;
        }

        return $derived;
    }
    /** @param list<string> $aliases @param list<string> $measures @return array<string,mixed> */
    private static function expression(mixed $value, string $path, array $aliases, array $measures, string $stage, array &$budget, int $depth = 0): array
    {
        if ($depth > 8) {
            self::fail('invalid_shape', $path, 'Resource Composition expression nesting is too deep.');
        }

        self::node($budget, $path);
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
                || ($literalType === 'string' && (! is_string($literal) || mb_strlen($literal) > self::MAX_STRING_LENGTH))
                || ($literalType === 'boolean' && ! is_bool($literal));
            if ($invalidLiteral) {
                self::fail('invalid_shape', "{$path}.value", 'Literal type does not match value.');
            }

            return ['kind' => 'literal', 'literal_type' => $literalType, 'value' => $literal];
        }
        if ($kind === 'coalesce') {
            self::assertKeys($expression, ['kind', 'values'], $path);
            $values = [];
            foreach (self::boundedList($expression['values'], "{$path}.values") as $index => $item) {
                $values[] = self::expression($item, "{$path}.values.{$index}", $aliases, $measures, $stage, $budget, $depth + 1);
            }

            return ['kind' => 'coalesce', 'values' => $values];
        }
        if ($kind === 'date_diff_days') {
            self::assertKeys($expression, ['kind', 'start', 'end'], $path);

            return ['kind' => $kind, 'start' => self::expression($expression['start'], "{$path}.start", $aliases, $measures, $stage, $budget, $depth + 1), 'end' => self::expression($expression['end'], "{$path}.end", $aliases, $measures, $stage, $budget, $depth + 1)];
        }
        if ($kind === 'case') {
            self::assertKeys($expression, ['kind', 'branches', 'otherwise'], $path);
            $branches = [];
            foreach (self::boundedList($expression['branches'], "{$path}.branches") as $index => $branch) {
                $branchPath = "{$path}.branches.{$index}";
                $branch = self::object($branch, $branchPath);
                self::assertKeys($branch, ['when', 'then'], $branchPath);
                $branches[] = ['when' => self::predicate($branch['when'], "{$branchPath}.when", $aliases, $budget, $depth + 1), 'then' => self::expression($branch['then'], "{$branchPath}.then", $aliases, $measures, $stage, $budget, $depth + 1)];
            }

            return ['kind' => 'case', 'branches' => $branches, 'otherwise' => self::expression($expression['otherwise'], "{$path}.otherwise", $aliases, $measures, $stage, $budget, $depth + 1)];
        }

        self::assertKeys($expression, ['kind', 'left', 'right'], $path);

        return ['kind' => $kind, 'left' => self::expression($expression['left'], "{$path}.left", $aliases, $measures, $stage, $budget, $depth + 1), 'right' => self::expression($expression['right'], "{$path}.right", $aliases, $measures, $stage, $budget, $depth + 1)];
    }
    private static function comparison(mixed $value, array $aliases, array $measures, array $derived, array &$budget): ?array
    {
        if ($value === null) return null;
        self::node($budget, 'comparison');
        $comparison = self::object($value, 'comparison');
        self::assertKeys($comparison, ['kind', 'dimension', 'targets'], 'comparison');
        $dimension = self::object($comparison['dimension'], 'comparison.dimension');
        self::assertKeys($dimension, ['source', 'field', 'bucket'], 'comparison.dimension');
        $targets = [];
        foreach (self::boundedList($comparison['targets'], 'comparison.targets') as $index => $item) {
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

    private static function sort(mixed $value, array $aliases, array $measures, array $derived, array &$budget): array
    {
        $sort = [];
        foreach (self::boundedList($value, 'sort') as $index => $item) {
            self::node($budget, "sort.{$index}");
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

    private static function limit(mixed $value): ?array
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
    private static function boundedList(mixed $value, string $path): array
    {
        $items = self::list($value, $path);
        if (count($items) > self::MAX_LIST_ITEMS) self::fail('invalid_shape', $path, 'Resource Composition list is too large.');

        return $items;
    }

    /** @return list<scalar> */
    private static function boundedScalarList(mixed $value, string $path): array
    {
        $items = self::boundedList($value, $path);
        if ($items === []) self::fail('invalid_filter_value', $path, 'The in filter requires a non-empty scalar list.');
        foreach ($items as $item) if (! self::isScalar($item)) self::fail('invalid_filter_value', $path, 'The in filter requires a scalar list.');

        return $items;
    }

    /** @param array{nodes:int} $budget */
    private static function node(array &$budget, string $path): void
    {
        if (++$budget['nodes'] > self::MAX_NODES) self::fail('invalid_shape', $path, 'Resource Composition is too complex.');
    }

    /** @param list<array<string,mixed>> $derived */
    private static function validateDerivedReferences(array $derived): void
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
    private static function sources(array $items): array
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

            if (isset($aliases[$alias])) {
                self::fail('duplicate_identifier', $path, 'Resource Composition source aliases must be unique.');
            }
            $aliases[$alias] = true;
            $resourceKeys[$resourceKey] = true;
            $sources[] = ['alias' => $alias, 'resource_key' => $resourceKey];
        }

        return $sources;
    }

    /** @param list<string> $aliases @return list<array{key: string, source: string, target: string, match: string, time_field?: string}> */
    private static function graphRelationships(mixed $value, array $aliases): array
    {
        $values = self::list($value, 'relationships');
        if ($values === [] && count($aliases) > 1) {
            self::fail('relationship_required', 'relationships', 'Multi-source Resource Composition requires at least one relationship.');
        }

        $relationships = [];
        $seen = [];
        foreach ($values as $index => $value) {
            $path = "relationships.{$index}";
            $relationship = self::object($value, $path);
            self::assertKeys(array_diff_key($relationship, ['time_field' => true]), ['key', 'source', 'target', 'match'], $path);
            $key = self::relationshipKey($relationship['key'] ?? null, "{$path}.key");
            $source = self::source($relationship['source'] ?? null, $aliases, "{$path}.source");
            $target = self::source($relationship['target'] ?? null, $aliases, "{$path}.target");
            $match = self::choice($relationship['match'] ?? null, ['preserve', 'require', 'exclude'], "{$path}.match", 'invalid_shape');
            self::assertUniqueTuple($seen, "{$key}\0{$source}\0{$target}", $path);
            $parsed = compact('key', 'source', 'target', 'match');
            if (array_key_exists('time_field', $relationship)) {
                $parsed['time_field'] = self::field($relationship['time_field'], "{$path}.time_field");
            }
            $relationships[] = $parsed;
        }

        return $relationships;
    }

    /** @param list<string> $aliases @return array{mode: string, source?: string} */
    private static function population(mixed $value, array $aliases, string $shape): array
    {
        $population = self::object($value, 'population');
        $mode = $population['mode'] ?? null;
        if ($mode === 'anchor') {
            self::assertKeys($population, ['mode', 'source'], 'population');
            return ['mode' => 'anchor', 'source' => self::source($population['source'], $aliases, 'population.source')];
        }

        self::assertKeys($population, ['mode'], 'population');
        $mode = self::choice($mode, ['union', 'intersection'], 'population.mode', 'invalid_shape');
        if ($shape !== 'aggregate') {
            self::fail('result_shape_mismatch', 'population.mode', 'Union and intersection populations require aggregate results.');
        }

        return ['mode' => $mode];
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

    private static function isScalar(mixed $value): bool
    {
        return (is_string($value) && mb_strlen($value) <= self::MAX_STRING_LENGTH)
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
