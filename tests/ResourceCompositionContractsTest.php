<?php

declare(strict_types=1);

use Nexia\ResourceComposition\CompositionSpec;
use Nexia\ResourceComposition\CompositionSpecValidationException;

require dirname(__DIR__).'/vendor/autoload.php';

$query = new \Illuminate\Database\Eloquent\Builder(new \Illuminate\Database\Query\Builder(new \Illuminate\Database\Connection(null)));
$fields = [
    'amount' => ['alias' => 'amount', 'type' => 'decimal', 'aggregations' => ['sum', 'avg'], 'unit' => 'currency', 'required_dimensions' => ['currency']],
    'currency' => ['alias' => 'currency', 'type' => 'string', 'groupable' => true, 'operators' => ['eq']],
];
$authorized = new \Nexia\Laravel\ResourceComposition\AuthorizedCompositionQuery($query, $fields);
if ($authorized->fields !== $fields || $authorized->query !== $query) {
    throw new RuntimeException('Authorized composition aggregate dependencies were not retained.');
}

$contents = file_get_contents(__DIR__.'/Fixtures/resource-composition-contracts.json');
$fixture = is_string($contents) ? json_decode($contents, true, flags: JSON_THROW_ON_ERROR) : null;
if (! is_array($fixture) || ! is_array($fixture['valid'] ?? null) || ! is_array($fixture['invalid'] ?? null)) {
    throw new RuntimeException('Resource Composition contract fixture is invalid.');
}

foreach ($fixture['valid'] as $input) {
    if (! is_array($input) || CompositionSpec::fromArray($input)->toArray() !== $input) {
        throw new RuntimeException('Resource Composition wire canonicalization changed unexpectedly.');
    }
}

$distinctCount = [
    'schema_version' => 1,
    'sources' => [['alias' => 'record', 'resource_key' => 'alpha.record']],
    'relationship' => null,
    'fields' => [],
    'filters' => [],
    'dimensions' => [],
    'measures' => [[
        'alias' => 'unique_records', 'source' => 'record', 'field' => 'public_id',
        'operation' => 'distinct_count', 'result_type' => 'integer',
        'null_behavior' => 'zero', 'unit' => 'count',
    ]],
    'result' => ['shape' => 'aggregate'],
];
if (CompositionSpec::fromArray($distinctCount)->toArray() !== $distinctCount) {
    throw new RuntimeException('Distinct count Resource Composition contract changed unexpectedly.');
}

$legacyLongString = str_repeat('x', 513);
$legacyLongFilter = $distinctCount;
$legacyLongFilter['filters'] = [['source' => 'record', 'field' => 'label', 'operator' => 'eq', 'value' => $legacyLongString]];
if (CompositionSpec::fromArray($legacyLongFilter)->toArray() !== $legacyLongFilter) {
    throw new RuntimeException('Legacy Resource Composition string limit changed unexpectedly.');
}

$v4 = ['schema_version' => 4, 'sources' => [['alias' => 'record', 'resource_key' => 'alpha.record']], 'relationships' => [], 'fields' => [], 'timezone' => 'Asia/Seoul', 'where' => ['kind' => 'all', 'children' => []], 'dimensions' => [], 'measures' => [['alias' => 'records', 'source' => 'record', 'field' => null, 'operation' => 'count', 'result_type' => 'integer', 'null_behavior' => 'zero', 'unit' => 'count', 'where' => ['kind' => 'all', 'children' => []]]], 'having' => ['kind' => 'all', 'children' => []], 'derived' => [], 'comparison' => null, 'sort' => [], 'limit' => null, 'result' => ['shape' => 'aggregate']];
if (CompositionSpec::fromArray($v4)->toArray() !== $v4) { throw new RuntimeException('Schema v4 Resource Composition contract changed unexpectedly.'); }

$deep = $v4['where'];
for ($index = 0; $index < 9; $index++) $deep = ['kind' => 'not', 'child' => $deep];
$invalidV4 = $v4; $invalidV4['where'] = $deep;
try { CompositionSpec::fromArray($invalidV4); throw new RuntimeException('Deep v4 predicate was accepted.'); } catch (CompositionSpecValidationException) {}
$invalidV4 = $v4; $invalidV4['measures'][0]['where'] = ['kind' => 'condition', 'target' => ['kind' => 'field', 'source' => 'record', 'field' => 'score'], 'operator' => 'eq', 'value' => INF];
try { CompositionSpec::fromArray($invalidV4); throw new RuntimeException('Non-finite v4 scalar was accepted.'); } catch (CompositionSpecValidationException) {}
$invalidV4 = $v4; $invalidV4['measures'][0]['where']['value'] = $legacyLongString;
try { CompositionSpec::fromArray($invalidV4); throw new RuntimeException('Overlong v4 scalar was accepted.'); } catch (CompositionSpecValidationException) {}

/** @param array<string, mixed> $input */
$set = static function (array &$input, string $path, mixed $value): void {
    $target = &$input;
    foreach (explode('.', $path) as $segment) {
        $key = ctype_digit($segment) ? (int) $segment : $segment;
        $target = &$target[$key];
    }
    $target = $value;
};

foreach ($fixture['invalid'] as $case) {
    if (! is_array($case)
        || ! is_string($case['base'] ?? null)
        || ! is_array($fixture['valid'][$case['base']] ?? null)
        || ! is_string($case['path'] ?? null)
        || ! is_string($case['code'] ?? null)
        || ! is_string($case['error_path'] ?? null)) {
        throw new RuntimeException('Resource Composition invalid-case fixture is malformed.');
    }

    $input = $fixture['valid'][$case['base']];
    $value = $case['value'] ?? null;
    if (is_int($case['repeat'] ?? null)) $value = array_fill(0, $case['repeat'], $value);
    $set($input, $case['path'], $value);
    try {
        CompositionSpec::fromArray($input);
        throw new RuntimeException("Invalid Resource Composition [{$case['name']}] was accepted.");
    } catch (CompositionSpecValidationException $exception) {
        if ($exception->errorCode !== $case['code']
            || $exception->path !== $case['error_path']
            || $exception->toArray() !== [
                'error_code' => $case['code'],
                'path' => $case['error_path'],
                'message' => $exception->getMessage(),
            ]) {
            throw new RuntimeException("Resource Composition error contract changed for [{$case['name']}].");
        }
    }
}

fwrite(STDOUT, "Resource Composition contracts passed.\n");
