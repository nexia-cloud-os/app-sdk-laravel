<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Nexia\Laravel\ResourceComposition\AuthorizedCompositionQuery;
use Nexia\ResourceComposition\CompositionSpec;
use Nexia\ResourceComposition\CompositionSpecValidationException;

require dirname(__DIR__).'/vendor/autoload.php';

$declared = [
    'public_id' => ['type' => 'uuid'],
    'status' => ['type' => 'string', 'enum' => ['active'], 'enum_labels' => ['active' => 'status.active']],
    'amount' => ['type' => 'decimal', 'nullable' => true, 'composition' => ['selectable' => true, 'aggregations' => ['sum', 'invalid'], 'required_dimensions' => ['currency']]],
    'worker_public_id' => ['type' => 'resource_reference', 'composition' => ['relationship_binding' => true]],
    'department_public_id' => ['type' => 'resource_reference', 'accepted_resource_keys' => ['trial.department']],
    'polymorphic_public_id' => ['type' => 'resource_reference', 'accepted_resource_keys' => ['trial.person', 'trial.organization']],
    'derived_reference' => ['type' => 'resource_reference', 'derived' => true, 'accepted_resource_keys' => ['trial.department']],
    'secret_token' => ['type' => 'string', 'composition' => ['selectable' => true]],
    'worker_id' => ['type' => 'integer', 'composition' => ['selectable' => true]],
    'virtual' => ['type' => 'string', 'composition' => ['selectable' => true]],
    'derived' => ['type' => 'string', 'derived' => true, 'composition' => ['selectable' => true]],
    'private_value' => ['type' => 'string'],
];
// Union schemas are not scalar composition fields; ignore them without PHP warnings.
$declared['nullable_union'] = ['type' => ['string', 'null'], 'composition' => ['selectable' => true]];
$declared['mixed_union'] = ['type' => ['string', 'number'], 'composition' => ['selectable' => true]];
$resolved = array_fill_keys(array_keys($declared), ['field_source' => 'descriptor', 'queryable' => true]);
$resolved['virtual']['queryable'] = false;
set_error_handler(static fn (int $level, string $message): never => throw new ErrorException($message, 0, $level));
$normalized = Nexia\ResourceComposition\DescriptorCompositionFields::build($declared, $resolved, ['status' => 'status.label'],
    static fn (string $key): ?string => ['status.active' => 'Active'][$key] ?? null);
restore_error_handler();
$byKey = array_column($normalized, null, 'key');
assert(array_keys($byKey) === ['public_id', 'status', 'amount', 'worker_public_id', 'department_public_id']);
assert($byKey['public_id']['selectable'] === true);
assert($byKey['status']['selectable'] === false && $byKey['status']['operators'] === ['eq', 'in']);
assert($byKey['status']['enum_labels'] === ['active' => 'Active'] && $byKey['status']['label'] === 'status.label');
assert($byKey['amount']['nullable'] === true && $byKey['amount']['aggregations'] === ['sum']);
assert($byKey['amount']['required_dimensions'] === ['currency']);
assert($byKey['worker_public_id']['selectable'] === false && $byKey['worker_public_id']['relationship_binding'] === true);
assert($byKey['department_public_id']['selectable'] === false && $byKey['department_public_id']['operators'] === []);
assert($byKey['department_public_id']['relationship_binding'] === true && $byKey['department_public_id']['reference_resource_key'] === 'trial.department');
assert($byKey['department_public_id']['label'] === 'department_public_id');

$query = new Builder(new Illuminate\Database\Query\Builder(new Connection(null)));
$fields = [
    'amount' => ['alias' => 'amount', 'type' => 'decimal', 'aggregations' => ['sum', 'avg'], 'unit' => 'currency', 'required_dimensions' => ['currency']],
    'currency' => ['alias' => 'currency', 'type' => 'string', 'groupable' => true, 'operators' => ['eq']],
];
$authorized = new AuthorizedCompositionQuery($query, $fields);
if ($authorized->fields !== $fields || $authorized->query !== $query) {
    throw new RuntimeException('Authorized composition aggregate dependencies were not retained.');
}

$temporalFields = [
    'worker_public_id' => [
        'alias' => 'worker_public_id', 'type' => 'resource_reference', 'reference_resource_key' => 'sample.worker',
        'temporal_interval' => [
            'start_field' => 'effective_from', 'end_field' => 'effective_until',
            'end_bound' => 'exclusive', 'null_start' => 'reject', 'null_end' => 'open',
        ],
    ],
    'effective_from' => ['alias' => 'effective_from', 'type' => 'date'],
    'effective_until' => ['alias' => 'effective_until', 'type' => 'date'],
];
new AuthorizedCompositionQuery($query, $temporalFields);
foreach ([
    [...$temporalFields['worker_public_id'], 'temporal_interval' => ['start_field' => 'effective_from', 'end_field' => 'effective_until', 'end_bound' => 'inclusive', 'null_start' => 'reject', 'null_end' => 'open']],
    [...$temporalFields['worker_public_id'], 'temporal_interval' => ['start_field' => 'effective_from', 'end_field' => 'missing', 'end_bound' => 'exclusive', 'null_start' => 'reject', 'null_end' => 'open']],
    ['alias' => 'worker_public_id', 'type' => 'string', 'temporal_interval' => $temporalFields['worker_public_id']['temporal_interval']],
    [...$temporalFields['worker_public_id'], 'temporal_interval' => [...$temporalFields['worker_public_id']['temporal_interval'], 'conversion' => 'implicit']],
] as $invalidTemporalSubject) {
    try {
        new AuthorizedCompositionQuery($query, [...$temporalFields, 'worker_public_id' => $invalidTemporalSubject]);
        throw new RuntimeException('Invalid temporal interval metadata was accepted.');
    } catch (InvalidArgumentException) {
        // Expected contract validation.
    }
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

$missingRowSource = $fixture['valid']['row_anchor'];
unset($missingRowSource['row_source']);
try {
    CompositionSpec::fromArray($missingRowSource);
    throw new RuntimeException('Row result accepted without row_source.');
} catch (CompositionSpecValidationException $exception) {
    if ($exception->errorCode !== 'invalid_shape' || $exception->path !== 'row_source') {
        throw new RuntimeException('Missing row_source error contract changed unexpectedly.');
    }
}

$deep = $fixture['valid']['aggregate_case_label'];
for ($index = 0; $index < 9; $index++) $deep['where'] = ['kind' => 'not', 'child' => $deep['where']];
try {
    CompositionSpec::fromArray($deep);
    throw new RuntimeException('Deep predicate was accepted.');
} catch (CompositionSpecValidationException) {
}

$nonFinite = $fixture['valid']['aggregate_case_label'];
$nonFinite['measures'][0]['where'] = ['kind' => 'condition', 'target' => ['kind' => 'field', 'source' => 'record', 'field' => 'score'], 'operator' => 'eq', 'value' => INF];
try {
    CompositionSpec::fromArray($nonFinite);
    throw new RuntimeException('Non-finite scalar was accepted.');
} catch (CompositionSpecValidationException) {
}

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
    if (is_int($case['repeat'] ?? null)) {
        $value = array_fill(0, $case['repeat'], $value);
    }
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
