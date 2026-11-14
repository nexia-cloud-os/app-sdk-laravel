<?php

declare(strict_types=1);

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;
use Nexia\ResourceReference\Contracts\ResourceReferenceSelections;
use Nexia\ResourceReference\PartySelectionConstraint;
use Nexia\ResourceReference\PartySelectionEligibility;
use Nexia\ResourceReference\PartySelectionType;
use Nexia\ResourceReference\ReferenceStatus;
use Nexia\ResourceReference\ResolvedResourceReference;
use Nexia\ResourceReference\ResourceRef;
use Nexia\ResourceReference\ResourceReferencePage;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;
use Nexia\ResourceReference\ResourceReferenceSelectionContext;
use Nexia\ResourceReference\ResourceReferenceSelectionPage;
use Nexia\ResourceReference\ResourceReferenceSelectionResult;

require dirname(__DIR__).'/vendor/autoload.php';

$reference = new ResourceRef(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.worker',
    resourceId: 'worker-01',
    display: 'Worker 01',
    href: '/apps/sample-owner/workers/worker-01',
);

if ($reference->toArray() !== [
    'app_key' => 'sample-owner',
    'resource_key' => 'sample-owner.worker',
    'resource_id' => 'worker-01',
    'display' => 'Worker 01',
    'href' => '/apps/sample-owner/workers/worker-01',
] || ResourceRef::fromArray($reference->toArray()) != $reference
    || ResourceRef::hasCanonicalIdentity('sample-owner', 'sample-other.item')
    || ResourceRef::hasCanonicalIdentity('sample-owner', 'sample-owner.worker..assignment')
    || ! ResourceRef::hasCanonicalIdentity('operating_unit', 'operating_unit.structure')
    || ResourceRef::hasCanonicalReference('sample-owner', 'sample-owner.worker', str_repeat('w', 256))
) {
    throw new RuntimeException('Resource reference contract changed unexpectedly.');
}

try {
    new ResourceRef('Sample-Owner', 'sample-owner.worker', 'worker-01', 'Worker 01');
    throw new RuntimeException('ResourceRef must reject a non-canonical app key.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

$resolved = new ResolvedResourceReference(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.worker',
    resourceId: 'worker-01',
    display: 'Worker 01',
    href: '/apps/sample-owner/workers/worker-01',
    fields: ['worker_number' => 'W-001', 'tags' => ['active']],
    asOf: new DateTimeImmutable('2026-07-27T10:00:00+09:00'),
    revision: 3,
    state: 'active',
);
assert(ResolvedResourceReference::fromSnapshot($resolved->snapshot())->snapshot() === $resolved->snapshot());
foreach ([['content_hash' => str_repeat('0', 64)], ['protected_values' => ['secret' => 'never']], ['schema_version' => 2]] as $invalid) {
    try {
        ResolvedResourceReference::fromSnapshot([...$resolved->snapshot(), ...$invalid]);
        throw new RuntimeException('Invalid snapshot accepted.');
    } catch (InvalidArgumentException) {
    }
}
$secret = 'protected-calendar-secret';
$protectedResolved = new ResolvedResourceReference(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.worker',
    resourceId: 'worker-01',
    display: 'Worker 01',
    href: '/apps/sample-owner/workers/worker-01',
    fields: ['worker_number' => 'W-001', 'tags' => ['active']],
    asOf: new DateTimeImmutable('2026-07-27T10:00:00+09:00'),
    revision: 3,
    state: 'active',
    protectedValues: ['calendar_token' => $secret],
);

$partySelection = PartySelectionConstraint::fromArray([
    'eligibility' => 'active_legal_entity_member',
    'types' => ['person', 'organization'],
]);
$actor = new class implements Actor
{
    public function key(): int|string
    {
        return 7;
    }

    public function publicId(): string
    {
        return 'actor-public-id';
    }

    public function displayLabel(): string
    {
        return 'Actor';
    }

    public function partyKey(): int|string|null
    {
        return 11;
    }

    public function isVerified(): bool
    {
        return true;
    }
};
$legalEntity = new class implements LegalEntity
{
    public function key(): int|string
    {
        return 3;
    }

    public function publicId(): string
    {
        return 'legal-entity-public-id';
    }

    public function displayLabel(): string
    {
        return 'Legal Entity';
    }

    public function code(): string
    {
        return 'LE';
    }

    public function partyPublicId(): ?string
    {
        return 'party-public-id';
    }

    public function isActiveOrganization(): bool
    {
        return true;
    }
};
$operatingUnit = new class implements OperatingUnit
{
    public function key(): int|string
    {
        return 5;
    }

    public function publicId(): string
    {
        return 'operating-unit-public-id';
    }

    public function displayLabel(): string
    {
        return 'Operating Unit';
    }

    public function parentKey(): int|string|null
    {
        return null;
    }

    public function code(): string
    {
        return 'OU';
    }

    public function classification(): string
    {
        return 'department';
    }

    public function accentColor(): ?string
    {
        return null;
    }
};
$resolutionContext = new ResourceReferenceResolutionContext(
    legalEntity: $legalEntity,
    actor: $actor,
    asOf: new DateTimeImmutable('2026-09-05T00:00:00+09:00'),
    purpose: 'sample.resolve',
    operatingUnit: $operatingUnit,
);
$boundedResolutionContext = new ResourceReferenceResolutionContext(
    legalEntity: $legalEntity,
    actor: $actor,
    asOf: new DateTimeImmutable('2026-09-05T00:00:00+09:00'),
    purpose: 'sample.resolve.protected',
    operatingUnit: $operatingUnit,
    includeProtectedValues: true,
    effectiveThrough: new DateTimeImmutable('2026-09-30T23:59:59+09:00'),
    filters: ['status' => ['ready', 'needs_review']],
    sort: '-employee',
);
$selectionContext = new ResourceReferenceSelectionContext(
    consumerResourceKey: 'sample.expense_report',
    consumerField: 'owner_party_public_id',
    targetResourceKey: 'directory.party',
    actor: $actor,
    legalEntity: $legalEntity,
    asOf: new DateTimeImmutable('2026-09-05T00:00:00+09:00'),
    operatingUnit: $operatingUnit,
);
$tenantSelectionContext = new ResourceReferenceSelectionContext(
    consumerResourceKey: 'sample.tenant_record',
    consumerField: 'owner_party_public_id',
    targetResourceKey: 'directory.party',
    actor: $actor,
    legalEntity: null,
    asOf: new DateTimeImmutable('2026-09-05T00:00:00+09:00'),
);
$selectionPage = new ResourceReferenceSelectionPage(
    ReferenceStatus::Available,
    new ResourceReferencePage([$resolved], 1, 1, 25, 1),
);

if ($partySelection->toArray() !== [
    'types' => ['person', 'organization'],
    'eligibility' => 'active_legal_entity_member',
] || $partySelection->types !== [PartySelectionType::Person, PartySelectionType::Organization]
    || $partySelection->eligibility !== PartySelectionEligibility::ActiveLegalEntityMember
    || $selectionContext->consumerField !== 'owner_party_public_id'
    || ! $selectionContext->forWrite
    || $tenantSelectionContext->legalEntity !== null
    || $resolutionContext->includeProtectedValues
    || $resolutionContext->effectiveThrough !== null
    || ! $boundedResolutionContext->includeProtectedValues
    || $boundedResolutionContext->effectiveThrough?->format('Y-m-d') !== '2026-09-30'
    || $boundedResolutionContext->filters !== ['status' => ['ready', 'needs_review']]
    || $boundedResolutionContext->sort !== '-employee'
    || $selectionPage->page->items !== [$resolved]
    || ! interface_exists(ResourceReferenceSelections::class)
    || (new ResourceReferenceSelectionResult(ReferenceStatus::Available, $resolved))->reference !== $resolved) {
    throw new RuntimeException('Declared Resource Reference selection contracts changed unexpectedly.');
}

try {
    new ResourceReferenceResolutionContext(
        legalEntity: $legalEntity,
        actor: $actor,
        asOf: new DateTimeImmutable('2026-09-05T00:00:00+09:00'),
        purpose: 'sample.resolve.inverted',
        effectiveThrough: new DateTimeImmutable('2026-09-04T23:59:59+09:00'),
    );
    throw new RuntimeException('Resource Reference resolution must reject an inverted effective interval.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

foreach ([
    fn () => new ResourceReferenceSelectionResult(ReferenceStatus::Disabled, $resolved),
    fn () => new ResourceReferenceSelectionPage(
        ReferenceStatus::Disabled,
        new ResourceReferencePage([$resolved], 1, 1, 25, 1),
    ),
] as $invalidSelection) {
    try {
        $invalidSelection();
        throw new RuntimeException('Contradictory Resource Reference selection values must be rejected.');
    } catch (InvalidArgumentException) {
        // Expected contract validation.
    }
}

try {
    new ResolvedResourceReference(
        appKey: 'sample-owner',
        resourceKey: 'sample-owner.worker',
        resourceId: str_repeat('w', 256),
        display: 'Worker 1',
        href: null,
        fields: [],
        asOf: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
    );
    throw new RuntimeException('ResolvedResourceReference must reject an oversized resource id.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

try {
    new ResolvedResourceReference(
        appKey: 'sample-owner',
        resourceKey: 'sample-owner.worker',
        resourceId: 'worker-1',
        display: 'Worker 1',
        href: null,
        fields: [],
        asOf: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        revision: -1,
    );
    throw new RuntimeException('ResolvedResourceReference must reject a negative revision.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}
$invalidResolvedValues = static fn (array $fields, array $protectedValues = []): ResolvedResourceReference => new ResolvedResourceReference(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.worker',
    resourceId: 'worker-1',
    display: 'Worker 1',
    href: null,
    fields: $fields,
    asOf: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
    protectedValues: $protectedValues,
);
foreach ([
    fn () => $invalidResolvedValues(['invalid' => new stdClass]),
    fn () => $invalidResolvedValues([], ['invalid' => new stdClass]),
    fn () => $invalidResolvedValues(['invalid']),
    fn () => $invalidResolvedValues([], ['invalid']),
] as $invalidValues) {
    try {
        $invalidValues();
        throw new RuntimeException('ResolvedResourceReference safe and protected fields must reject non-JSON values.');
    } catch (InvalidArgumentException) {
        // Expected contract validation.
    }
}
$snapshot = $resolved->snapshot();
$protectedSnapshot = $protectedResolved->snapshot();
$nativeSerialization = serialize($protectedResolved);
$restoredProtected = unserialize($nativeSerialization);
ob_start();
var_dump($protectedResolved);
$debugDump = (string) ob_get_clean();
$ordinarySurfaces = [
    json_encode($protectedResolved, JSON_THROW_ON_ERROR),
    $nativeSerialization,
    var_export($protectedResolved, true),
    print_r($protectedResolved, true),
    $debugDump,
];

if ($resolved->reference() !== $reference->toArray()
    || $snapshot['reference'] !== $reference->toArray()
    || $snapshot['revision'] !== 3
    || $snapshot['status'] !== 'active'
    || ! preg_match('/^[a-f0-9]{64}$/', $snapshot['content_hash'])
    || $resolved->hasProtectedValues()
    || $resolved->protectedValues() !== []
    || $protectedResolved->protectedValues() !== ['calendar_token' => $secret]
    || ! $protectedResolved->hasProtectedValues()
    || array_key_exists('protectedValues', get_object_vars($protectedResolved))
    || $protectedSnapshot !== $snapshot
    || ! $restoredProtected instanceof ResolvedResourceReference
    || $restoredProtected->snapshot() !== $snapshot
    || $restoredProtected->hasProtectedValues()
    || $restoredProtected->protectedValues() !== []
    || array_any($ordinarySurfaces, static fn (string $output): bool => str_contains($output, $secret))
    || ResourceReferencePage::empty(0, 0) != new ResourceReferencePage([], 1, 1, 1, 0)
) {
    throw new RuntimeException('Resolved resource reference contracts changed unexpectedly.');
}

try {
    new ResourceReferencePage([$reference], 1, 1, 25, 1);
    throw new RuntimeException('ResourceReferencePage must reject unresolved references.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

try {
    new ResourceReferencePage([], 1, 2, 25, 0);
    throw new RuntimeException('ResourceReferencePage must reject contradictory totals.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

$calls = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(function (array $body) use (&$calls): array {
    $calls[] = $body;

    return [];
});
assert($transport->resolveMany('sample-owner.worker', [], $resolutionContext) === []);
assert($calls === []);
$ids = array_map(static fn (int $id): string => 'worker-'.$id, range(1, 50));
$transport->resolveMany('sample-owner.worker', [...$ids, ...$ids], $resolutionContext);
assert(count($calls) === 1 && $calls[0]['resource_ids'] === $ids);
assert($calls[0]['context']['actor_public_id'] === $actor->publicId());
assert($calls[0]['context']['purpose'] === $resolutionContext->purpose);
$transport->resolveMany('sample-owner.worker', array_fill(0, 101, 'worker-one'), $resolutionContext);
assert(count($calls) === 2 && $calls[1]['resource_ids'] === ['worker-one']);
$transport->resolveMany('sample-owner.worker', array_map(static fn (int $id): string => 'worker-'.$id, range(1, 100)), $resolutionContext);
assert(count($calls) === 3 && count($calls[2]['resource_ids']) === 100);
foreach ([[1], [''], [str_repeat('x', 256)], [...$ids, ...$ids, '']] as $invalidIds) {
    try {
        $transport->resolveMany('sample-owner.worker', $invalidIds, $resolutionContext);
        throw new RuntimeException('Invalid batch must fail before transport.');
    } catch (InvalidArgumentException) {
        assert(count($calls) === 3);
    }
}
$chunks = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$chunks, $resolutionContext): array {
    $chunks[] = $body['resource_ids'];
    $results = [];
    foreach ($body['resource_ids'] as $id) {
        if ($id !== 'missing') {
            $results[$id] = (new ResolvedResourceReference('sample-owner', 'sample-owner.worker', $id, $id, null, [], $resolutionContext->asOf))->snapshot();
        }
    }

    return $results;
});
$largeIds = array_map(static fn (int $id): string => (string) $id, range(1, 200));
$large = $transport->resolveMany('sample-owner.worker', [...$largeIds, 'missing', '1'], $resolutionContext);
assert(array_map('count', $chunks) === [100, 100, 1]);
assert(count($large) === 200 && $large[101]->resourceId === '101' && ! isset($large['missing']));
$chunks = [];
$large = $transport->resolveMany('sample-owner.worker', array_slice($largeIds, 0, 101), $resolutionContext);
assert(array_map('count', $chunks) === [100, 1] && count($large) === 101);
$chunks = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$chunks): array {
    $chunks[] = $body['resource_ids'];
    if (count($chunks) === 2) {
        throw new RuntimeException('Second batch unavailable.');
    }

    return [];
});
try {
    $transport->resolveMany('sample-owner.worker', [...$largeIds, 'last'], $resolutionContext);
    throw new LogicException('A failed batch must not return partial success.');
} catch (RuntimeException $error) {
    assert($error->getMessage() === 'Second batch unavailable.' && count($chunks) === 2);
}
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static fn (): array => ['worker-01' => $resolved->snapshot()]);
assert($transport->resolveMany('sample-owner.worker', ['worker-01', 'missing'], $resolutionContext)['worker-01']->resourceId === 'worker-01');

$groupCalls = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$groupCalls): array {
    $groupCalls[] = $body;
    $results = [];
    foreach ($body['groups'] as $key => $group) {
        $results[$key]['worker-01'] = (new ResolvedResourceReference(
            'sample-owner', 'sample-owner.worker', 'worker-01', 'Worker', null, [], new DateTimeImmutable($group['as_of']),
        ))->snapshot();
    }

    return $results;
});
$grouped = $transport->resolveManyGrouped('sample-owner.worker', [
    'first' => ['resource_ids' => ['worker-01'], 'as_of' => new DateTimeImmutable('2026-01-01'), 'filters' => ['region' => 'north']],
    'second' => ['resource_ids' => ['worker-01'], 'as_of' => new DateTimeImmutable('2026-02-01'), 'filters' => ['region' => 'south']],
], $resolutionContext);
assert(count($groupCalls) === 1 && $groupCalls[0]['method'] === 'resolveManyGrouped');
assert($groupCalls[0]['groups']['first']['filters']['region'] === 'north');
assert($groupCalls[0]['groups']['second']['filters']['region'] === 'south');
assert($grouped['first']['worker-01']->asOf->format('Y-m-d') === '2026-01-01');
assert($grouped['second']['worker-01']->asOf->format('Y-m-d') === '2026-02-01');
$groupCalls = [];
$transport->resolveManyGrouped('sample-owner.worker', array_fill_keys(
    array_map(static fn (int $day): string => 'day-'.$day, range(1, 32)),
    ['resource_ids' => ['worker-01'], 'as_of' => new DateTimeImmutable('2026-01-01')],
), $resolutionContext);
assert(count($groupCalls) === 2 && count($groupCalls[0]['groups']) === 31 && count($groupCalls[1]['groups']) === 1);

$groupCalls = [];
$groupIds = array_map(static fn (int $index): string => 'worker-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), range(1, 201));
$groupedTransport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$groupCalls): array {
    $groupCalls[] = $body;
    $result = [];
    foreach ($body['groups'] as $key => $group) {
        foreach ($group['resource_ids'] as $id) {
            if ($id === 'worker-100') continue;
            $result[$key][$id] = (new ResolvedResourceReference(
                'sample-owner', 'sample-owner.worker', $id, 'Worker', null, [], new DateTimeImmutable($group['as_of']),
            ))->snapshot();
        }
    }

    return $result;
});
$manyGroups = [
    'first' => ['resource_ids' => $groupIds, 'as_of' => new DateTimeImmutable('2026-01-01'), 'filters' => ['region' => 'north']],
    'second' => ['resource_ids' => $groupIds, 'as_of' => new DateTimeImmutable('2026-02-01'), 'filters' => ['region' => 'south']],
];
$many = $groupedTransport->resolveManyGrouped('sample-owner.worker', $manyGroups, $resolutionContext);
assert(count($groupCalls) === 3);
assert(array_keys($many) === ['first', 'second'] && count($many['first']) === 200 && count($many['second']) === 200);
assert($many['first']['worker-201']->asOf->format('Y-m-d') === '2026-01-01');
assert($many['second']['worker-201']->asOf->format('Y-m-d') === '2026-02-01');
foreach ($groupCalls as $call) {
    assert(array_keys($call['groups']) === ['first', 'second']);
    assert(count($call['groups']['first']['resource_ids']) <= 100 && count($call['groups']['second']['resource_ids']) <= 100);
    assert($call['groups']['first']['filters']['region'] === 'north' && $call['groups']['second']['filters']['region'] === 'south');
    assert($call['context']['purpose'] === $resolutionContext->purpose);
    assert($call['context']['actor_public_id'] === $resolutionContext->actor->publicId());
}
$invalidGroup = new \Nexia\ResourceReference\TransportResourceReferences(static fn (): array => ['foreign' => []]);
try {
    $invalidGroup->resolveManyGrouped('sample-owner.worker', $manyGroups, $resolutionContext);
    throw new LogicException('Unknown grouped responses must fail.');
} catch (RuntimeException $error) {
    assert($error->getMessage() === 'Invalid grouped reference response.');
}
$invalidMember = new \Nexia\ResourceReference\TransportResourceReferences(static fn (): array => ['first' => ['foreign' => $resolved->snapshot()]]);
try {
    $invalidMember->resolveManyGrouped('sample-owner.worker', $manyGroups, $resolutionContext);
    throw new LogicException('An unrequested group member must fail.');
} catch (RuntimeException $error) {
    assert($error->getMessage() === 'Unexpected grouped reference.');
}
$attempts = 0;
$failedGroup = new \Nexia\ResourceReference\TransportResourceReferences(static function () use (&$attempts): array {
    if (++$attempts === 2) throw new RuntimeException('Grouped batch unavailable.');
    return [];
});
try {
    $failedGroup->resolveManyGrouped('sample-owner.worker', $manyGroups, $resolutionContext);
    throw new LogicException('A failed grouped batch must not return partial success.');
} catch (RuntimeException $error) {
    assert($error->getMessage() === 'Grouped batch unavailable.' && $attempts === 2);
}

$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body, Closure $decode) use ($resolved) {
    return $decode($resolved->snapshot());
});
assert($transport->resolve('sample-owner.worker', 'worker-01', $resolutionContext)->snapshot() === $resolved->snapshot());
$public = ResolvedResourceReference::fromSnapshot($resolved->snapshot());
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $public);
assert($transport->resolve('sample-owner.worker', 'worker-01', $resolutionContext) === $public);
$private = new ResolvedResourceReference('sample-owner', 'sample-owner.worker', 'worker-01', 'Worker', null, [], $resolutionContext->asOf, protectedValues: ['secret' => 'private']);
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $private);
try {
    $transport->resolve('sample-owner.worker', 'worker-01', $resolutionContext);
    throw new LogicException('Protected transport objects must fail.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'Invalid SDK resource snapshot.');
}

fwrite(STDOUT, "Resource reference contract passed.\n");


// Owner batching is an optional extension; legacy batch providers remain valid.
assert(is_subclass_of(
    \Nexia\ResourceReference\Contracts\ResourceReferenceGroupedResolutionContribution::class,
    \Nexia\ResourceReference\Contracts\ResourceReferenceBatchResolutionContribution::class,
));
$groupedOwnerMethod = new ReflectionMethod(
    \Nexia\ResourceReference\Contracts\ResourceReferenceGroupedResolutionContribution::class,
    'resolveManyGrouped',
);
assert($groupedOwnerMethod->getNumberOfRequiredParameters() === 2);
assert((string) $groupedOwnerMethod->getReturnType() === 'array');

$rangeQueries = array_map(static fn ($i): string => 'worker-'.$i, range(1, 201));
$rangeCalls = [];
$rangeReference = static fn (string $id, string $date = '2026-09-23'): array => (new ResolvedResourceReference(
    'sample-owner', 'sample-owner.worker', $id, 'Synthetic worker', null, [], new DateTimeImmutable($date),
))->snapshot();
$transport = new \Nexia\ResourceReference\TransportResourceReferences(
    static function (array $body) use (&$rangeCalls, $rangeReference, $resolutionContext): array {
        assert($body['method'] === 'searchEffectiveRange' && count($body['queries']) <= 100);
        assert($body['context']['actor_public_id'] === $resolutionContext->actor->publicId());
        assert($body['context']['purpose'] === $resolutionContext->purpose);
        assert($body['from'] === '2026-01-01T00:00:00+00:00' && $body['until'] === '2026-12-31T00:00:00+00:00');
        $rangeCalls[] = $body['queries'];
        return array_slice([$rangeReference('shared'), ...array_map($rangeReference, $body['queries'])], 0, $body['limit']);
    },
);
$from = new DateTimeImmutable('2026-01-01');
$until = new DateTimeImmutable('2026-12-31');

$limitCalls = [];
$limitTransport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$limitCalls): array {
    $limitCalls[] = $body['limit'];
    return [];
});
$limitTransport->searchEffectiveRange('sample-owner.worker', ['worker'], $from, $until, $resolutionContext);
$limitTransport->searchEffectiveRange('sample-owner.worker', ['worker'], $from, $until, $resolutionContext, 50000);
$limitTransport->searchEffectiveRange('sample-owner.worker', ['worker'], $from, $until, $resolutionContext, 100000);
assert($limitCalls === [10000, 50000, 100000]);
try {
    $limitTransport->searchEffectiveRange('sample-owner.worker', ['worker'], $from, $until, $resolutionContext, 100001);
    throw new LogicException('Out-of-range limit accepted.');
} catch (InvalidArgumentException) {}
assert(count($limitCalls) === 3);

$rangeResult = $transport->searchEffectiveRange('sample-owner.worker', [...$rangeQueries, $rangeQueries[0]], $from, $until, $resolutionContext);
assert(array_map('count', $rangeCalls) === [100, 100, 1]);
assert(array_merge(...$rangeCalls) === $rangeQueries && count($rangeResult) === 202);
assert(count(array_filter($rangeResult, static fn ($r) => $r->resourceId === 'shared')) === 1);
$rangeCalls = [];
assert(count($transport->searchEffectiveRange('sample-owner.worker', $rangeQueries, $from, $until, $resolutionContext, 2)) === 2);
assert(count($rangeCalls) === 1, 'Reaching the caller limit must not dispatch more callbacks.');
assert($transport->searchEffectiveRange('sample-owner.worker', [], $from, $until, $resolutionContext) === []);
foreach ([['too-long-'.str_repeat('x', 160)], [1]] as $invalidQueries) {
    try {
        $transport->searchEffectiveRange('sample-owner.worker', $invalidQueries, $from, $until, $resolutionContext);
        throw new LogicException('Invalid range selector accepted.');
    } catch (InvalidArgumentException) {}
}
assert(count($rangeCalls) === 1);
$rangeCalls = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function ($body) use (&$rangeCalls, $rangeReference): array {
    $rangeCalls[] = $body;
    if (count($rangeCalls) === 2) throw new RuntimeException('Later range batch unavailable.');
    return [$rangeReference('first')];
});
try {
    $transport->searchEffectiveRange('sample-owner.worker', $rangeQueries, $from, $until, $resolutionContext);
    throw new LogicException('Partial range search accepted.');
} catch (RuntimeException $error) {
    assert($error->getMessage() === 'Later range batch unavailable.' && count($rangeCalls) === 2);
}
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function ($body) use ($rangeReference): array {
    return [$rangeReference('same-worker', $body['queries'][0] === 'worker-1' ? '2026-01-01' : '2026-06-01')];
});
assert(count($transport->searchEffectiveRange('sample-owner.worker', $rangeQueries, $from, $until, $resolutionContext)) === 2,
    'Distinct historical snapshots must not be merged just because their resource ID matches.');
foreach ([null, [[]]] as $invalidResult) {
    $transport = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $invalidResult);
    try {
        $transport->searchEffectiveRange('sample-owner.worker', ['worker-1'], $from, $until, $resolutionContext);
        throw new LogicException('Invalid range response accepted.');
    } catch (RuntimeException) {}
}

// Independent searches keep their own scope, result and failure in one transport.
$searchCalls = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(
    static function (): array { throw new LogicException('Unexpected individual call'); },
    static function (array $bodies) use (&$searchCalls, $resolved): array {
        $searchCalls[] = $bodies;
        return [['status' => 200, 'result' => [$resolved->snapshot()]], ['status' => 403]];
    },
);
$searches = [
    ['resource_key' => 'sample-owner.worker', 'query' => '', 'context' => $resolutionContext, 'limit' => 100],
    ['resource_key' => 'other.item', 'query' => 'sample', 'context' => $resolutionContext, 'limit' => 25],
];
$batchResult = $transport->searchBatch($searches);
assert(count($searchCalls) === 1 && count($searchCalls[0]) === 2);
assert($searchCalls[0][0]['context']['actor_public_id'] === $actor->publicId());
assert($searchCalls[0][1]['context']['purpose'] === $resolutionContext->purpose);
assert($batchResult[0]['result'][0]->snapshot() === $resolved->snapshot() && $batchResult[1] === ['status' => 403]);
assert($transport->searchBatch([]) === [] && count($searchCalls) === 1);
$chunkSizes = [];
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [],
    static function (array $chunk) use (&$chunkSizes): array {
        $chunkSizes[] = count($chunk);
        return array_fill(0, count($chunk), ['status' => 200, 'result' => []]);
    });
assert(count($transport->searchBatch(array_fill(0, 5, $searches[0]))) === 5 && $chunkSizes === [2, 2, 1]);
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$searchCalls, $resolved): array {
    $searchCalls[] = $body;
    if ($body['resource_key'] === 'other.item') throw new RuntimeException('rejected');
    return [$resolved->snapshot()];
});
try {
    $transport->searchBatch($searches);
    throw new LogicException('Individual fallback must propagate its original error.');
} catch (RuntimeException $error) {
    assert($error->getMessage() === 'rejected');
}
$transport = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [], static fn () => [['status' => 200, 'result' => []]]);
try {
    $transport->searchBatch($searches);
    throw new LogicException('Incomplete batch response accepted.');
} catch (RuntimeException) {}

// Exact reads can opt in to protected fields without changing public snapshots.
$protectedWire = [];
$protectedAt = $boundedResolutionContext->asOf;
$protectedReference = new ResolvedResourceReference('sample-owner', 'sample-owner.worker', 'worker', 'Synthetic', null, [], $protectedAt,
    protectedValues: ['synthetic' => 'private-fixture']);
$protectedSnapshot = [...$protectedReference->snapshot(), 'protected_values' => $protectedReference->protectedValues()];
$protectedClient = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$protectedWire, $protectedSnapshot): mixed {
    $protectedWire[] = $body;
    return $body['method'] === 'resolve' ? $protectedSnapshot : ['worker' => $protectedSnapshot];
});
$oneProtected = $protectedClient->resolve('sample-owner.worker', 'worker', $boundedResolutionContext);
$manyProtected = $protectedClient->resolveMany('sample-owner.worker', ['worker'], $boundedResolutionContext);
assert($oneProtected->hasProtectedValues() && $manyProtected['worker']->hasProtectedValues());
assert($oneProtected->protectedValues() === $manyProtected['worker']->protectedValues());
assert(($protectedWire[0]['context']['include_protected_values'] ?? null) === true);
assert(($protectedWire[0]['standard_query'] ?? null) === true);
assert(! str_contains(json_encode($oneProtected->snapshot(), JSON_THROW_ON_ERROR), 'private-fixture'));
foreach (['search', 'searchPage'] as $method) {
    try { $protectedClient->{$method}('sample-owner.worker', '', $boundedResolutionContext); throw new LogicException('Protected search accepted.'); }
    catch (RuntimeException $error) { assert(str_contains($error->getMessage(), 'only for exact')); }
}
assert(count($protectedWire) === 2);
$unexpectedProtected = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $protectedSnapshot);
try { $unexpectedProtected->resolve('sample-owner.worker', 'worker', $resolutionContext); throw new LogicException('Unrequested protected values accepted.'); }
catch (RuntimeException $error) { assert($error->getMessage() === 'Invalid SDK resource snapshot.'); }
$malformedProtected = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [...$protectedSnapshot, 'protected_values' => 'not-a-map']);
try { $malformedProtected->resolve('sample-owner.worker', 'worker', $boundedResolutionContext); throw new LogicException('Malformed protected values accepted.'); }
catch (RuntimeException $error) { assert($error->getMessage() === 'Invalid SDK resource snapshot.'); }
echo "Protected exact reference transport passed.\n";

foreach (['invalid', [...$protectedSnapshot, 'protected_values' => null],
    [...$protectedSnapshot, 'protectedValues' => []]] as $invalidProtected) {
    $invalidClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $invalidProtected);
    try { $invalidClient->resolve('sample-owner.worker', 'worker', $boundedResolutionContext); throw new LogicException('Invalid protected response accepted.'); }
    catch (RuntimeException $error) { assert($error->getMessage() === 'Invalid SDK resource snapshot.'); }
}
$extraClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => ['other' => $protectedSnapshot]);
try { $extraClient->resolveMany('sample-owner.worker', ['worker'], $boundedResolutionContext); throw new LogicException('Unexpected ID accepted.'); }
catch (RuntimeException $error) { assert($error->getMessage() === 'Invalid SDK resource snapshot.'); }
$wrongClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $protectedSnapshot);
try { $wrongClient->resolve('sample-owner.worker', 'other', $boundedResolutionContext); throw new LogicException('Wrong ID accepted.'); }
catch (RuntimeException $error) { assert($error->getMessage() === 'Invalid SDK resource snapshot.'); }
$legacyAttempts = 0;
$legacyHost = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$legacyAttempts): never {
    $legacyAttempts++;
    assert(($body['context']['include_protected_values'] ?? null) === true);
    throw new \Symfony\Component\HttpKernel\Exception\HttpException(422);
});
try { $legacyHost->resolve('sample-owner.worker', 'worker', $boundedResolutionContext); throw new LogicException('Legacy rejection ignored.'); }
catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $error) { assert($error->getStatusCode() === 422); }
assert($legacyAttempts === 1);
$complete = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use ($resolved) {
    if ($body['method'] !== 'searchCompleteEffectiveRange' || $body['queries'] !== ['first', 'second']) {
        throw new RuntimeException('Complete range request changed unexpectedly.');
    }
    return [$resolved->snapshot()];
});
$completeResult = $complete->searchCompleteEffectiveRange('sample-owner.worker', ['first', 'second'], new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31'), $resolutionContext);
if (count($completeResult) !== 1 || $completeResult[0]->resourceId !== 'worker-01') {
    throw new RuntimeException('Complete range response changed unexpectedly.');
}
$unsupported = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => null);
if ($unsupported->searchCompleteEffectiveRange('sample-owner.worker', ['first'], new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31'), $resolutionContext) !== null) {
    throw new RuntimeException('Unsupported complete range was not preserved.');
}
$invalidComplete = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [$resolved->snapshot(), $resolved->snapshot()]);
try {
    $invalidComplete->searchCompleteEffectiveRange('sample-owner.worker', ['first'], new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-12-31'), $resolutionContext);
    throw new RuntimeException('Duplicate complete range reference accepted.');
} catch (RuntimeException $error) {
    if ($error->getMessage() === 'Duplicate complete range reference accepted.') throw $error;
}
$completeFrom = new DateTimeImmutable('2026-01-01');
$completeUntil = new DateTimeImmutable('2026-12-31');
$completeStandard = $complete->query('sample-owner.worker', $resolutionContext)->searches(['first', 'second'])
    ->effectiveRange($completeFrom, $completeUntil)->complete()->get();
assert($completeStandard['status'] === 'success' && $completeStandard['data'][0]->snapshot() === $completeResult[0]->snapshot());
$completeUnavailable = $unsupported->query('sample-owner.worker', $resolutionContext)->searches(['first'])
    ->effectiveRange($completeFrom, $completeUntil)->complete()->get();
assert($completeUnavailable['status'] === 'error' && $completeUnavailable['error']['code'] === 'COMPLETE_RANGE_UNAVAILABLE');
$emptyComplete = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => []);
$emptyCompleteResult = $emptyComplete->query('sample-owner.worker', $resolutionContext)->searches(['first'])
    ->effectiveRange($completeFrom, $completeUntil)->complete()->get();
assert($emptyCompleteResult['status'] === 'success' && $emptyCompleteResult['data'] === []);
assert($invalidComplete->query('sample-owner.worker', $resolutionContext)->searches(['first'])
    ->effectiveRange($completeFrom, $completeUntil)->complete()->get()['error']['code'] === 'QUERY_FAILED');
assert($complete->query('sample-owner.worker', $resolutionContext)->searches([])->effectiveRange($completeFrom, $completeUntil)
    ->complete()->get()['error']['code'] === 'INVALID_ARGUMENT');
assert($complete->query('sample-owner.worker', $resolutionContext)->searches(['first'])->effectiveRange($completeFrom, $completeUntil)
    ->limit(100)->complete()->get()['error']['code'] === 'INVALID_ARGUMENT');
// Standard queries retain the legacy wire operations while normalizing their response.
$queryWire = [];
$queryBatchWire = [];
$queryClient = new \Nexia\ResourceReference\TransportResourceReferences(
    static function (array $body) use (&$queryWire, $rangeReference): mixed {
        $queryWire[] = $body;
        return match ($body['method']) {
            'resolve' => $body['resource_id'] === 'missing' ? null : $rangeReference($body['resource_id'], $body['context']['as_of']),
            'resolveMany' => collect($body['resource_ids'])->filter(fn ($id) => $id !== 'missing')
                ->mapWithKeys(fn ($id) => [$id => $rangeReference($id, $body['context']['as_of'])])->all(),
            'resolveManyGrouped', 'resolveManyGroupedWithSchema' => collect($body['groups'])->map(fn ($group) => collect($group['resource_ids'])
                ->mapWithKeys(fn ($id) => [$id => $rangeReference($id, $group['as_of'])])->all())->all(),
            'search' => [$rangeReference('search-result', $body['context']['as_of'])],
            'searchPage', 'searchPageWithSchema', 'searchEffectiveRangePage' => ($body['page'] ?? 1) === 9 ? null :
                ['items' => [$rangeReference('page-result', $body['context']['as_of'])], 'current_page' => 1, 'last_page' => 1, 'per_page' => $body['per_page'], 'total' => 1],
            'searchEffectiveRange' => array_map($rangeReference, $body['queries']),
            default => throw new LogicException('Unexpected fixture operation.'),
        };
    },
    static function (array $bodies) use (&$queryBatchWire, $rangeReference): array {
        $queryBatchWire[] = $bodies;
        return [['status' => 200, 'result' => [$rangeReference('batch-result', $bodies[0]['context']['as_of'])]], ['status' => 403]];
    },
);
$keys = ['status', 'data', 'page', 'error'];
$beforeInvalidArguments = count($queryWire);
try {
    $queryClient->query('sample-owner.worker', $resolutionContext, ['id' => 'one']);
    throw new LogicException('Obsolete array query arguments were silently ignored.');
} catch (InvalidArgumentException) {}
try {
    $queryClient->queryBatch([], ['ignored']);
    throw new LogicException('Extra batch arguments were silently ignored.');
} catch (InvalidArgumentException) {}
assert(count($queryWire) === $beforeInvalidArguments);
$initialCount = count($queryWire);
$queryBase = $queryClient->query('sample-owner.worker', $resolutionContext);
$builderBatch = $queryClient->queryBatch([]);
$extraArgumentCalls = [
    fn () => $queryBase->where('id', '>', 1),
    fn () => $queryBase->whereIn('id', ['one'], 'unexpected'),
    fn () => $queryBase->search('one', 'unexpected'),
    fn () => $queryBase->searches(['one'], 'unexpected'),
    fn () => $queryBase->complete('unexpected'),
    fn () => $queryBase->limit(10, 'unexpected'),
    fn () => $queryBase->page(1, 10, 'unexpected'),
    fn () => $queryBase->effectiveRange($from, $until, 'unexpected'),
    fn () => $queryBase->asOf($from, 'unexpected'),
    fn () => $queryBase->effectiveThrough($until, 'unexpected'),
    fn () => $queryBase->orderBy('id', 'asc', 'unexpected'),
    fn () => $queryBase->allowLegacyContextFilters('unexpected'),
    fn () => $queryBase->options([], 'unexpected'),
    fn () => $queryBase->get('unexpected'),
    fn () => $queryBase->snapshot($queryClient, 'unexpected'),
    fn () => $builderBatch->allowSharedFailure('unexpected'),
    fn () => $builderBatch->get('unexpected'),
    fn () => new \Nexia\ResourceReference\ResourceReferenceQuery($queryClient, 'sample-owner.worker', $resolutionContext, static fn () => [], 'unexpected'),
    fn () => new \Nexia\ResourceReference\ResourceReferenceQueryBatch($queryClient, [], static fn () => [], false, 'unexpected'),
];
foreach ($extraArgumentCalls as $attempt) {
    try {
        $attempt();
        throw new LogicException('Extra fluent arguments were silently ignored.');
    } catch (InvalidArgumentException) {}
}
assert(count($queryWire) === $initialCount);
$queryOne = $queryBase->where('id', 'one');
$queryTwo = $queryBase->where('id', 'two');
assert(count($queryWire) === $initialCount);
assert($queryOne->get()['data'][0]->resourceId === 'one' && $queryTwo->get()['data'][0]->resourceId === 'two');
assert($queryBase->where('id', 'one')->where('id', 'two')->get()['error']['code'] === 'INVALID_ARGUMENT');
assert($queryBase->whereIn('id', ['one'])->whereIn('id', ['two'])->get()['error']['code'] === 'INVALID_ARGUMENT');
assert($queryBase->search('one')->search('two')->get()['error']['code'] === 'INVALID_ARGUMENT');
assert($queryBase->search('')->page(1, 5)->page(2, 5)->get()['error']['code'] === 'INVALID_ARGUMENT');
$filteredQuery = $queryBase->whereIn('state', ['active', 'planned'])->orderBy('label', 'desc')->get();
assert($filteredQuery['status'] === 'success' && end($queryWire)['method'] === 'searchPageWithSchema');
assert(end($queryWire)['context']['filters']['state'] === ['active', 'planned'] && end($queryWire)['context']['sort'] === '-label');
assert($queryBase->where('state', 'active')->options(['_query_schema' => false])->get()['status'] === 'success'
    && end($queryWire)['method'] === 'searchPageWithSchema');
assert($queryBase->where('state', 'active')->allowLegacyContextFilters()->get()['status'] === 'success'
    && end($queryWire)['method'] === 'searchPageWithSchema');
assert($queryBase->where('id', 'one')->where('state', 'active')->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION');
$strictContext = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
    $resolutionContext->asOf, $resolutionContext->purpose, $resolutionContext->operatingUnit,
    effectiveThrough: $resolutionContext->asOf, filters: ['state' => 'active'], sort: '-label');
$strictId = $queryClient->query('sample-owner.worker', $strictContext)->where('id', 'one');
$before = count($queryWire);
assert($strictId->get()['status'] === 'success' && count($queryWire) - $before === 1
    && end($queryWire)['method'] === 'resolveManyGroupedWithSchema');
$unboundedStrict = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
    $resolutionContext->asOf, $resolutionContext->purpose, $resolutionContext->operatingUnit,
    filters: ['state' => 'active'], sort: '-label');
$before = count($queryWire);
assert($queryClient->query('sample-owner.worker', $unboundedStrict)->where('id', 'one')->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION'
    && count($queryWire) === $before);
assert($strictId->allowLegacyContextFilters()->get()['status'] === 'success'
    && end($queryWire)['method'] === 'resolve' && end($queryWire)['context']['filters'] === ['state' => 'active']);
$strictPage = $queryClient->query('sample-owner.worker', $strictContext)->search('')->page(1, 5)->get();
assert($strictPage['status'] === 'success' && end($queryWire)['method'] === 'searchPageWithSchema');
$strictGroup = $queryClient->query('sample-owner.worker', $strictContext)->whereIn('id', ['one']);
$before = count($queryWire);
$grouped = $queryClient->queryBatch([$strictGroup, $strictGroup])->allowSharedFailure()->get();
assert($grouped['status'] === 'success' && count($queryWire) - $before === 1
    && end($queryWire)['method'] === 'resolveManyGroupedWithSchema');
$singleGrouped = $queryClient->queryBatch([$strictGroup])->allowSharedFailure()->get();
assert($singleGrouped['data'][0]['data'][0]->snapshot() === $strictGroup->get()['data'][0]->snapshot());
$before = count($queryWire);
$mixedModes = $queryClient->queryBatch([$strictGroup->allowLegacyContextFilters(), $strictGroup])->allowSharedFailure()->get();
assert($mixedModes['status'] === 'success' && count($queryWire) - $before === 2
    && array_column(array_slice($queryWire, -2), 'method') === ['resolveMany', 'resolveManyGroupedWithSchema']);
$individualWire = [];
$individualClient = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$individualWire): array {
    $individualWire[] = $body['method'];
    if ($body['method'] !== 'resolveMany') throw new \Symfony\Component\HttpKernel\Exception\HttpException(422);
    $owner = explode('.', $body['resource_key'], 2)[0];
    $id = $body['resource_ids'][0];
    $reference = new ResolvedResourceReference($owner, $body['resource_key'], $id, 'Synthetic', null, [],
        new DateTimeImmutable($body['context']['as_of']));
    return [$id => $reference->snapshot()];
});
$independent = $individualClient->queryBatch([
    $individualClient->query('sample-owner.worker', $resolutionContext)->whereIn('id', ['one'])->effectiveThrough($resolutionContext->asOf),
    $individualClient->query('other.item', $resolutionContext)->whereIn('id', ['two'])->effectiveThrough($resolutionContext->asOf),
])->allowSharedFailure()->get();
assert($independent['status'] === 'success' && $individualWire === ['resolveMany', 'resolveMany']);
$individualWire = [];
$legacyIndependent = $individualClient->queryBatch([
    $individualClient->query('sample-owner.worker', $strictContext)->whereIn('id', ['one'])->allowLegacyContextFilters(),
    $individualClient->query('other.item', $strictContext)->whereIn('id', ['two'])->allowLegacyContextFilters(),
])->allowSharedFailure()->get();
assert($legacyIndependent['status'] === 'success' && $individualWire === ['resolveMany', 'resolveMany']);
$queryWire = [];
$one = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one'])->get();
$legacyOne = $queryClient->resolve('sample-owner.worker', 'one', $resolutionContext);
$standardWire = $queryWire[0];
assert($standardWire['standard_query'] === true);
unset($standardWire['standard_query']);
assert(array_keys($one) === $keys && $one['status'] === 'success' && $one['data'][0]->snapshot() === $legacyOne->snapshot()
    && $one['page'] === null && $one['error'] === null && $standardWire === $queryWire[1]);
assert($queryClient->query('sample-owner.worker', $resolutionContext)->options(['id' => 'missing'])->get()['data'] === []);
$ids = array_map(fn ($i) => 'id-'.$i, range(1, 205));
$many = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['ids' => [...$ids, 'missing', 'id-1']])->get();
$legacyMany = $queryClient->resolveMany('sample-owner.worker', [...$ids, 'missing', 'id-1'], $resolutionContext);
assert(count($many['data']) === 205 && array_map(fn ($r) => $r->snapshot(), $many['data']) === array_map(fn ($r) => $r->snapshot(), array_values($legacyMany)));
assert($queryClient->query('sample-owner.worker', $resolutionContext)->options(['ids' => []])->get()['data'] === []);
$list = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['search' => 'example'])->get();
assert($list['data'][0]->snapshot() === $queryClient->search('sample-owner.worker', 'example', $resolutionContext)[0]->snapshot());
$page = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['search' => 'example', 'page' => 1])->get();
$legacyPage = $queryClient->searchPage('sample-owner.worker', 'example', $resolutionContext);
assert($page['data'][0]->snapshot() === $legacyPage->items[0]->snapshot()
    && $page['page'] === ['number' => 1, 'last' => 1, 'perPage' => 25, 'total' => 1, 'hasMore' => false]);
$range = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['one'], 'from' => $from, 'until' => $until])->get();
assert($range['data'][0]->snapshot() === $queryClient->searchEffectiveRange('sample-owner.worker', ['one'], $from, $until, $resolutionContext)[0]->snapshot());
$normalizedWire = [];
$normalizedRange = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$normalizedWire): array {
    $normalizedWire[] = $body;
    return [];
});
$normalizedResult = $normalizedRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['', '  ', ' one ', 'one'], 'from' => $from, 'until' => $until, 'limit' => 50000])->get();
assert($normalizedResult['status'] === 'success' && $normalizedResult['data'] === []);
assert($normalizedWire[0]['queries'] === ['', '  ', ' one ', 'one'] && $normalizedWire[0]['limit'] === 50000);
$normalizedRange->searchEffectiveRange('sample-owner.worker', ['', '  ', ' one ', 'one'], $from, $until, $resolutionContext, 50000);
assert($normalizedWire[1]['queries'] === $normalizedWire[0]['queries']);
$emptyResult = $normalizedRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['', '  '], 'from' => $from, 'until' => $until])->get();
assert($emptyResult['status'] === 'success' && $emptyResult['data'] === [] && count($normalizedWire) === 3
    && $normalizedWire[2]['queries'] === ['', '  ']);
$tooLarge = $normalizedRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['one'], 'from' => $from, 'until' => $until, 'limit' => 100001])->get();
assert($tooLarge['error']['code'] === 'INVALID_ARGUMENT' && count($normalizedWire) === 3);
$rangePage = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['from' => $from, 'until' => $until])->get();
assert($rangePage['data'][0]->snapshot() === $queryClient->searchEffectiveRangePage('sample-owner.worker', $from, $until, $resolutionContext)->items[0]->snapshot());
$unavailable = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['search' => '', 'page' => 9])->get();
assert($unavailable === ['status' => 'error', 'data' => [], 'page' => null, 'error' => ['code' => 'PAGE_UNAVAILABLE', 'httpStatus' => null]]);
$bad = $queryClient->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one', 'search' => 'conflicting'])->get();
assert($bad['status'] === 'error' && $bad['error']['code'] === 'INVALID_ARGUMENT' && $bad['data'] === []);
$denied = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'private upstream details'));
$deniedResult = $denied->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one'])->get();
assert($deniedResult['error'] === ['code' => 'FORBIDDEN', 'httpStatus' => 403]
    && ! str_contains(json_encode($deniedResult, JSON_THROW_ON_ERROR), 'private upstream details'));
try { $denied->resolve('sample-owner.worker', 'one', $resolutionContext); throw new LogicException('Legacy denial must propagate.'); }
catch (\Symfony\Component\HttpKernel\Exception\HttpException $error) { assert($error->getStatusCode() === 403); }
$batchContext = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
    new DateTimeImmutable('2026-01-01'), $resolutionContext->purpose, $resolutionContext->operatingUnit,
    effectiveThrough: new DateTimeImmutable('2026-01-31'), filters: ['state' => ['active']]);
$batchContext2 = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
    new DateTimeImmutable('2026-02-01'), $resolutionContext->purpose, $resolutionContext->operatingUnit,
    effectiveThrough: new DateTimeImmutable('2026-02-28'), filters: ['state' => ['planned']]);
$entry = static fn ($context, $options) => ['resource_key' => 'sample-owner.worker', 'context' => $context,
    'options' => [...$options, '_allow_legacy_context_filters' => true]];
$batch = $queryClient->queryBatch([
    $entry($batchContext, ['ids' => ['first']]), $entry($batchContext2, ['ids' => ['second']]),
    $entry($batchContext, ['search' => 'one']), $entry($batchContext2, ['search' => 'two']),
    $entry($batchContext, ['id' => 'last']), $entry($batchContext, ['id' => 'x', 'search' => 'bad']),
])->allowSharedFailure()->get();
assert(array_keys($batch) === $keys && $batch['status'] === 'partial' && $batch['error'] === null && $batch['page'] === null);
assert(array_column($batch['data'], 'status') === ['success', 'success', 'success', 'error', 'success', 'error']);
foreach ($batch['data'] as $result) assert(array_keys($result) === $keys);
assert($batch['data'][0]['data'][0]->asOf == $batchContext->asOf && $batch['data'][1]['data'][0]->asOf == $batchContext2->asOf
    && $batch['data'][3]['error']['code'] === 'FORBIDDEN' && $batch['data'][3]['error']['httpStatus'] === 403
    && $batch['data'][4]['data'][0]->resourceId === 'last' && $batch['data'][5]['error']['code'] === 'INVALID_ARGUMENT');
assert(count($queryBatchWire) === 1 && count($queryBatchWire[0]) === 2);
// Legacy grouped requests default their end date to each group's as-of date.
$datedGroups = [];
$datedRequests = [];
for ($day = 1; $day <= 31; $day++) {
    $date = new DateTimeImmutable(sprintf('2026-01-%02d', $day));
    $group = ['resource_ids' => ['worker'], 'as_of' => $date];
    $datedGroups['day-'.$day] = $group;
    $context = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
        $date, $resolutionContext->purpose, $resolutionContext->operatingUnit, effectiveThrough: $date);
    $datedRequests[] = $entry($context, ['ids' => ['worker']]);
}
$before = count($queryWire);
$legacyDated = $queryClient->resolveManyGrouped('sample-owner.worker', $datedGroups, $resolutionContext);
assert(count($queryWire) - $before === 1);
$before = count($queryWire);
$standardDated = $queryClient->queryBatch($datedRequests)->allowSharedFailure()->get();
assert($standardDated['status'] === 'success' && count($queryWire) - $before === 1);
foreach (array_values($legacyDated) as $index => $references) {
    assert(array_values($references)[0]->snapshot() === $standardDated['data'][$index]['data'][0]->snapshot());
}
$tooLargeResponse = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => throw new \Symfony\Component\HttpKernel\Exception\HttpException(413));
$tooLargeResult = $tooLargeResponse->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['one'], 'from' => $from, 'until' => $until, 'limit' => 50000])->get();
assert($tooLargeResult['status'] === 'error' && $tooLargeResult['error']['code'] === 'TOO_LARGE' && $tooLargeResult['data'] === []);
// Standard reads reject substituted identities and dates without exposing snapshots.
$at = $resolutionContext->asOf->format(DATE_ATOM);
foreach ([
    $rangeReference('other', $at),
    (new ResolvedResourceReference('other', 'other.worker', 'one', 'Synthetic', null, [], new DateTimeImmutable($at)))->snapshot(),
    $rangeReference('one', '2020-01-01'),
] as $invalidSnapshot) {
    $untrusted = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $invalidSnapshot);
    $invalid = $untrusted->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one'])->get();
    assert($invalid['status'] === 'error' && $invalid['error']['code'] === 'QUERY_FAILED' && $invalid['data'] === []);
}
$extra = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => ['other' => $rangeReference('other', $at)]);
assert($extra->query('sample-owner.worker', $resolutionContext)->options(['ids' => ['one']])->get()['status'] === 'error');
$wrongSearch = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [$rangeReference('one', '2020-01-01')]);
assert($wrongSearch->query('sample-owner.worker', $resolutionContext)->options(['search' => 'one'])->get()['status'] === 'error');
$protectedCalls = [];
$protectedTransport = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$protectedCalls): mixed {
    $protectedCalls[] = $body['method'];
    $id = $body['resource_id'] ?? $body['resource_ids'][0];
    $reference = new ResolvedResourceReference('sample-owner', 'sample-owner.worker', $id, 'Synthetic', null, [],
        new DateTimeImmutable($body['context']['as_of']), protectedValues: ['synthetic' => 'private-fixture']);
    $snapshot = [...$reference->snapshot(), 'protected_values' => $reference->protectedValues()];
    return $body['method'] === 'resolve' ? $snapshot : [$id => $snapshot];
});
$protectedResult = $protectedTransport->query('sample-owner.worker', $boundedResolutionContext)->where('id', 'one')->allowLegacyContextFilters()->get();
assert($protectedResult['status'] === 'success' && $protectedResult['data'][0]->hasProtectedValues());
$protectedBatch = $protectedTransport->queryBatch([
    ['resource_key' => 'sample-owner.worker', 'context' => $boundedResolutionContext, 'options' => ['ids' => ['one'], '_allow_legacy_context_filters' => true]],
    ['resource_key' => 'sample-owner.worker', 'context' => $boundedResolutionContext, 'options' => ['id' => 'two', '_allow_legacy_context_filters' => true]],
    ['resource_key' => 'sample-owner.worker', 'context' => $boundedResolutionContext, 'options' => ['search' => 'x', '_allow_legacy_context_filters' => true]],
])->get();
assert($protectedBatch['status'] === 'partial' && $protectedBatch['data'][0]['data'][0]->hasProtectedValues()
    && $protectedBatch['data'][1]['data'][0]->hasProtectedValues()
    && $protectedBatch['data'][2]['error']['code'] === 'PROTECTED_VALUES_UNAVAILABLE');
assert($protectedCalls === ['resolve', 'resolveMany', 'resolve']);
foreach ([['items' => []], ['items' => [], 'current_page' => 2, 'last_page' => 1, 'per_page' => 25, 'total' => 0], 'bad'] as $invalidPage) {
    $invalidTransport = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $invalidPage);
    $invalid = $invalidTransport->query('sample-owner.worker', $resolutionContext)->options(['search' => '', 'page' => 1])->get();
    assert($invalid['status'] === 'error' && $invalid['error']['code'] === 'QUERY_FAILED' && $invalid['data'] === []);
}
$unsafeFailure = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => throw new \Nexia\ResourceReference\ReferenceQueryFailure('private-upstream-detail', 999));
$unsafeResult = $unsafeFailure->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one'])->get();
assert($unsafeResult['error'] === ['code' => 'QUERY_FAILED', 'httpStatus' => null]);
assert(! str_contains(json_encode($unsafeResult, JSON_THROW_ON_ERROR), 'private-upstream-detail'));
$invalidGrouped = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => null);
$badGrouped = $invalidGrouped->queryBatch([$entry($batchContext, ['ids' => ['a']]), $entry($batchContext2, ['ids' => ['b']])])->allowSharedFailure()->get();
assert($badGrouped['status'] === 'error' && array_column($badGrouped['data'], 'status') === ['error', 'error']);
$invalidRange = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => ['unexpected-key' => $rangeReference('one')]);
assert($invalidRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['one'], 'from' => $from, 'until' => $until])->get()['status'] === 'error');
$emptyBatch = $queryClient->queryBatch([])->get();
assert($emptyBatch === ['status' => 'success', 'data' => [], 'page' => null, 'error' => null]);
$badBatch = $queryClient->queryBatch([['resource_key' => 'sample-owner.worker']])->get();
assert($badBatch['status'] === 'error' && $badBatch['error']['code'] === 'INVALID_BATCH');
$allDenied = $denied->queryBatch([$entry($resolutionContext, ['id' => 'one']), $entry($resolutionContext, ['id' => 'two'])])->get();
assert($allDenied['status'] === 'error' && $allDenied['error'] === null && count($allDenied['data']) === 2);
$failing = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use ($rangeReference): mixed {
    if ($body['method'] === 'resolveManyGrouped') throw new RuntimeException('private group failure');
    return $rangeReference($body['resource_id'], $body['context']['as_of']);
});
$partial = $failing->queryBatch([$entry($batchContext, ['ids' => ['a']]), $entry($batchContext2, ['ids' => ['b']]), $entry($batchContext, ['id' => 'c'])])->allowSharedFailure()->get();
assert($partial['status'] === 'partial' && array_column($partial['data'], 'status') === ['error', 'error', 'success']
    && $partial['data'][0]['error']['code'] === 'QUERY_FAILED' && ! str_contains(json_encode($partial, JSON_THROW_ON_ERROR), 'private group failure'));

// An unsupported optional batch override falls back to supported individual reads.
readonly class RestrictedBatchSearchFixture extends \Nexia\ResourceReference\TransportResourceReferences
{
    public function searchBatch(array $searches): array { return []; }
}
$restrictedBatchSearch = new RestrictedBatchSearchFixture(static function (array $body) use ($rangeReference): mixed {
    return $body['method'] === 'search'
        ? [$rangeReference($body['query'], $body['context']['as_of'])]
        : $rangeReference($body['resource_id'], $body['context']['as_of']);
});
$mixed = $restrictedBatchSearch->queryBatch([
    $entry($resolutionContext, ['search' => 'one']), $entry($resolutionContext, ['search' => 'two']),
    $entry($resolutionContext, ['id' => 'one']),
])->get();
assert($mixed['status'] === 'success' && array_column($mixed['data'], 'status') === ['success', 'success', 'success']);
assert($mixed['data'][0]['data'][0]->resourceId === 'one' && $mixed['data'][2]['data'][0]->resourceId === 'one');
$pageFallback = new RestrictedBatchSearchFixture(static fn (array $body) => [
    'items' => [], 'current_page' => $body['page'], 'last_page' => 1, 'per_page' => $body['per_page'], 'total' => 0,
]);
$pageBatch = $pageFallback->queryBatch([
    $pageFallback->query('sample-owner.worker', $resolutionContext)->search('one')->page(1, 10),
    $pageFallback->query('sample-owner.worker', $resolutionContext)->search('two')->page(1, 10),
])->get();
assert($pageBatch['status'] === 'success' && array_column($pageBatch['data'], 'status') === ['success', 'success']);

// A subclass's legacy override must never be bypassed by an inherited standard method.
readonly class RestrictedExactReferenceFixture extends \Nexia\ResourceReference\TransportResourceReferences
{
    public function resolve(string $resourceKey, string $resourceId, ResourceReferenceResolutionContext $context): ?ResolvedResourceReference
    {
        return null;
    }
}
readonly class RestrictedManyReferenceFixture extends \Nexia\ResourceReference\TransportResourceReferences
{
    public function resolveMany(string $resourceKey, array $resourceIds, ResourceReferenceResolutionContext $context): array
    {
        return [];
    }
}
$restrictedExactWire = 0;
$restrictedExact = new RestrictedExactReferenceFixture(static function () use (&$restrictedExactWire): array {
    $restrictedExactWire++;
    return [];
});
assert($restrictedExact->resolve('sample-owner.worker', 'one', $strictContext) === null);
$restrictedExactQuery = $restrictedExact->query('sample-owner.worker', $strictContext)->where('id', 'one');
assert($restrictedExactQuery->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION' && $restrictedExactWire === 0);
assert($restrictedExact->queryBatch([$restrictedExactQuery])->allowSharedFailure()->get()['data'][0]['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION'
    && $restrictedExactWire === 0);
$restrictedManyWire = 0;
$restrictedMany = new RestrictedManyReferenceFixture(static function () use (&$restrictedManyWire): array {
    $restrictedManyWire++;
    return [];
});
assert($restrictedMany->resolveMany('sample-owner.worker', ['one'], $strictContext) === []);
$restrictedManyQuery = $restrictedMany->query('sample-owner.worker', $strictContext)->whereIn('id', ['one']);
assert($restrictedManyQuery->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION' && $restrictedManyWire === 0);
assert($restrictedMany->queryBatch([$restrictedManyQuery])->allowSharedFailure()->get()['data'][0]['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION'
    && $restrictedManyWire === 0);
readonly class RestrictedReferenceQueryFixture extends \Nexia\ResourceReference\TransportResourceReferences
{
    public function search(string $resourceKey, string $query, ResourceReferenceResolutionContext $context, int $limit = 25): array
    {
        return [];
    }
}
$restrictedCalls = 0;
$restricted = new RestrictedReferenceQueryFixture(static function () use (&$restrictedCalls, $resolved): array {
    $restrictedCalls++;
    return [$resolved->snapshot()];
});
assert($restricted->search('sample-owner.worker', '', $resolutionContext) === []);
assert($restricted->query('sample-owner.worker', $resolutionContext)->options(['search' => ''])->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION');
$restrictedBatch = $restricted->queryBatch([
    $entry($resolutionContext, ['search' => 'first']), $entry($resolutionContext, ['search' => 'second']),
])->get();
assert(array_column($restrictedBatch['data'], 'status') === ['error', 'error'] && $restrictedCalls === 0);
foreach ([
    ['ids' => [42]], ['ids' => ['' ]],
    ['search' => '', 'page' => '0'], ['search' => '', 'per_page' => '1001'], ['search' => '', 'limit' => '0'],
    ['queries' => [42], 'from' => $from, 'until' => $until],
    ['queries' => ['one'], 'from' => $until, 'until' => $from],
] as $invalidOptions) {
    $invalid = $queryClient->query('sample-owner.worker', $resolutionContext)->options($invalidOptions)->get();
    assert($invalid['status'] === 'error' && $invalid['error']['code'] === 'INVALID_ARGUMENT');
}
$boundaryWire = [];
$boundary = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$boundaryWire) {
    $boundaryWire[] = $body;
    return $body['method'] === 'resolveMany' ? [] : null;
});
foreach (['', ' id ', '   ', str_repeat('x', 256)] as $id) {
    assert($boundary->resolve('sample-owner.worker', $id, $resolutionContext) === null);
    assert($boundary->query('sample-owner.worker', $resolutionContext)->where('id', $id)->get()['status'] === 'success');
    $standard = array_pop($boundaryWire); $legacy = array_pop($boundaryWire); unset($standard['standard_query']);
    assert($standard === $legacy);
}
assert($boundary->resolveMany('sample-owner.worker', ['a' => ' id ', 'b' => '   '], $resolutionContext) === []);
assert($boundary->query('sample-owner.worker', $resolutionContext)->whereIn('id', ['a' => ' id ', 'b' => '   '])->get()['status'] === 'success');
$standard = array_pop($boundaryWire); $legacy = array_pop($boundaryWire); unset($standard['standard_query']);
assert($standard === $legacy);
$callback401 = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => throw new \Symfony\Component\HttpKernel\Exception\HttpException(401));
assert($callback401->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one'])->get()['error']
    === ['code' => 'UPSTREAM_AUTHENTICATION_FAILED', 'httpStatus' => 502]);
$callback401Batch = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [], static fn () => [['status' => 401], ['status' => 200, 'result' => []]]);
$callback401Results = $callback401Batch->queryBatch([$entry($resolutionContext, ['search' => 'one']), $entry($resolutionContext, ['search' => 'two'])])->get();
assert($callback401Results['data'][0]['error'] === ['code' => 'UPSTREAM_AUTHENTICATION_FAILED', 'httpStatus' => 502]);
$translator = new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader, 'en');
$validator = new \Illuminate\Validation\Validator($translator, [], []);
$frameworkFailures = [
    [new \Illuminate\Validation\ValidationException($validator), ['code' => 'INVALID_QUERY', 'httpStatus' => 422]],
    [new \Illuminate\Auth\Access\AuthorizationException('private-fixture'), ['code' => 'FORBIDDEN', 'httpStatus' => 403]],
    [(new \Illuminate\Auth\Access\AuthorizationException('private-fixture'))->asNotFound(), ['code' => 'NOT_FOUND', 'httpStatus' => 404]],
    [(new \Illuminate\Database\Eloquent\ModelNotFoundException)->setModel('PrivateFixture', ['private-fixture']), ['code' => 'NOT_FOUND', 'httpStatus' => 404]],
    [new \Illuminate\Auth\AuthenticationException('private-fixture'), ['code' => 'UNAUTHENTICATED', 'httpStatus' => 401]],
];
foreach ($frameworkFailures as [$frameworkError, $expectedFailure]) {
    $frameworkClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => throw $frameworkError);
    $failure = $frameworkClient->query('sample-owner.worker', $resolutionContext)->where('id', 'one')->get();
    assert($failure['status'] === 'error' && $failure['error'] === $expectedFailure
        && ! str_contains(json_encode($failure, JSON_THROW_ON_ERROR), 'private-fixture'));
}
foreach (['RUNTIME_RESOURCE_READ_NOT_GRANTED' => 503, 'RUNTIME_REFERENCE_DEADLINE_EXCEEDED' => 504,
    'RUNTIME_REFERENCE_CYCLE' => 508, 'RUNTIME_REFERENCE_DEPTH_EXCEEDED' => 508] as $failureCode => $status) {
    $runtimeError = new class($failureCode) extends RuntimeException {
        public readonly ?int $upstreamStatus;
        public function __construct(public readonly string $failureCode) { $this->upstreamStatus = null; }
    };
    $failed = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => throw $runtimeError);
    assert($failed->query('sample-owner.worker', $resolutionContext)->options(['id' => 'one'])->get()['error']
        === ['code' => $failureCode, 'httpStatus' => $status]);
}
$normalizedQueries = null;
$normalized = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$normalizedQueries): array {
    $normalizedQueries = $body['queries'];
    return [];
});
$normalized->query('sample-owner.worker', $resolutionContext)->options(['queries' => [' one ', 'one', ' two '], 'from' => $from, 'until' => $until])->get();
assert($normalizedQueries === [' one ', 'one', ' two ']);
$normalized->searchEffectiveRange('sample-owner.worker', [' one ', 'one'], $from, $until, $resolutionContext);
assert($normalizedQueries === [' one ', 'one']);
$duplicateRange = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [
    $rangeReference('same-worker', '2026-01-01'), $rangeReference('same-worker', '2026-06-01'),
]);
assert(count($duplicateRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => ['one'], 'from' => $from, 'until' => $until])->get()['data']) === 2);
assert(count($duplicateRange->searchEffectiveRange('sample-owner.worker', ['one'], $from, $until, $resolutionContext)) === 2);
$sameInstant = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [
    $rangeReference('same-worker', '2026-01-01'), $rangeReference('same-worker', '2026-01-01'),
]);
assert(count($sameInstant->searchEffectiveRange('sample-owner.worker', ['one'], $from, $until, $resolutionContext)) === 1);
assert(count($sameInstant->query('sample-owner.worker', $resolutionContext)->searches(['one'])->effectiveRange($from, $until)->get()['data']) === 1);
$brokenInstant = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => [
    $rangeReference('same-worker', '2026-01-01'), ['invalid' => true],
]);
try {
    $brokenInstant->searchEffectiveRange('sample-owner.worker', ['one'], $from, $until, $resolutionContext);
    throw new LogicException('Malformed duplicate accepted.');
} catch (RuntimeException $error) {
    assert($error->getMessage() !== 'Malformed duplicate accepted.');
}
assert($brokenInstant->query('sample-owner.worker', $resolutionContext)->searches(['one'])->effectiveRange($from, $until)->get()['error']['code'] === 'QUERY_FAILED');
$rangeChunkCalls = [];
$chunkedRange = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$rangeChunkCalls, $rangeReference): array {
    $rangeChunkCalls[] = count($body['queries']);
    return [$rangeReference('same-worker', $body['queries'][0] === 'worker-1' ? '2026-01-01' : '2026-06-01')];
});
$chunkedResult = $chunkedRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => $rangeQueries, 'from' => $from, 'until' => $until])->get();
assert($rangeChunkCalls === [100, 100, 1] && $chunkedResult['status'] === 'success' && count($chunkedResult['data']) === 2);
$rangeChunkCalls = [];
assert($chunkedRange->query('sample-owner.worker', $resolutionContext)->options(['queries' => [], 'from' => $from, 'until' => $until])->get()['data'] === [] && $rangeChunkCalls === []);
$nullBatchCalls = [];
$nullBatchClient = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$nullBatchCalls, $rangeReference): array {
    $nullBatchCalls[] = $body;
    assert($body['context']['effective_through'] === null);
    return collect($body['resource_ids'])->mapWithKeys(fn ($id) => [$id => $rangeReference($id, $body['context']['as_of'])])->all();
});
$nullBatchResult = $nullBatchClient->queryBatch([
    $entry($resolutionContext, ['ids' => ['first', 'shared']]),
    $entry($resolutionContext, ['ids' => ['first', 'shared']]),
])->get();
assert(count($nullBatchCalls) === 1 && $nullBatchCalls[0]['resource_ids'] === ['first', 'shared']);
assert(array_map(fn ($r) => $r->resourceId, $nullBatchResult['data'][0]['data']) === ['first', 'shared']);
assert(array_map(fn ($r) => $r->resourceId, $nullBatchResult['data'][1]['data']) === ['first', 'shared']);
$nullBatchCalls = [];
$differentOrder = $nullBatchClient->queryBatch([
    $entry($resolutionContext, ['ids' => ['first', 'shared']]),
    $entry($resolutionContext, ['ids' => ['shared', 'first']]),
])->get();
assert(count($nullBatchCalls) === 2
    && array_map(fn ($r) => $r->resourceId, $differentOrder['data'][0]['data']) === ['first', 'shared']
    && array_map(fn ($r) => $r->resourceId, $differentOrder['data'][1]['data']) === ['shared', 'first']);
$nullBatchCalls = [];
$differentDate = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
    $resolutionContext->asOf->modify('+1 day'), $resolutionContext->purpose, $resolutionContext->operatingUnit);
$differentFilters = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
    $resolutionContext->asOf, $resolutionContext->purpose, $resolutionContext->operatingUnit, filters: ['status' => 'active']);
$nullBatchClient->queryBatch([
    $entry($resolutionContext, ['ids' => ['first']]), $entry($differentDate, ['ids' => ['second']]),
    $entry($differentFilters, ['ids' => ['third']]),
])->get();
assert(count($nullBatchCalls) === 3);
$sameDateCalls = [];
$sameDateClient = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$sameDateCalls, $rangeReference): array {
    $sameDateCalls[] = $body;
    return collect($body['resource_ids'])->mapWithKeys(fn ($id) => [$id => $rangeReference($id, $body['context']['as_of'])])->all();
});
$sameDateResult = $sameDateClient->queryBatch([
    $entry($batchContext, ['ids' => ['first']]), $entry($batchContext, ['ids' => ['first']]),
])->get();
assert(count($sameDateCalls) === 1 && $sameDateCalls[0]['method'] === 'resolveMany'
    && $sameDateCalls[0]['context']['effective_through'] === $batchContext->effectiveThrough->format(DATE_ATOM)
    && $sameDateResult['data'][0]['data'][0]->resourceId === 'first'
    && $sameDateResult['data'][1]['data'][0]->resourceId === 'first');
$groupMethods = [];
$groupedClient = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$groupMethods): array {
    $groupMethods[] = $body['method'];
    return [];
});
$groupQueries = [];
$legacyGroups = [];
foreach (range(0, 30) as $offset) {
    $date = (new DateTimeImmutable('2026-01-01'))->modify('+'.$offset.' days');
    $selected = new ResourceReferenceResolutionContext($resolutionContext->legalEntity, $resolutionContext->actor,
        $date, $resolutionContext->purpose, $resolutionContext->operatingUnit, effectiveThrough: $date);
    $groupQueries[] = $entry($selected, ['ids' => ['same']]);
    $legacyGroups['day-'.$offset] = ['resource_ids' => ['same'], 'as_of' => $date];
}
assert($groupedClient->queryBatch($groupQueries)->allowSharedFailure()->get()['status'] === 'success' && $groupMethods === ['resolveManyGrouped']);
$groupMethods = [];
$groupedClient->resolveManyGrouped('sample-owner.worker', $legacyGroups, $resolutionContext);
assert($groupMethods === ['resolveManyGrouped']);
readonly class RestrictedGroupedReferenceQueryFixture extends \Nexia\ResourceReference\TransportResourceReferences
{
    public function resolveManyGrouped(string $resourceKey, array $groups, ResourceReferenceResolutionContext $context): array
    {
        return [];
    }
}
$restrictedGroupCalls = 0;
$restrictedGroup = new RestrictedGroupedReferenceQueryFixture(static function () use (&$restrictedGroupCalls): array {
    $restrictedGroupCalls++;
    return [];
});
$blockedGroupBatch = $restrictedGroup->queryBatch([$entry($batchContext, ['ids' => ['one']]), $entry($batchContext2, ['ids' => ['two']])])->allowSharedFailure()->get();
assert($blockedGroupBatch['status'] === 'success' && $blockedGroupBatch['error'] === null
    && array_column($blockedGroupBatch['data'], 'status') === ['success', 'success'] && $restrictedGroupCalls === 2);
$singleGroupBatch = $restrictedGroup->queryBatch([$entry($batchContext, ['ids' => ['one']])])->get();
assert($singleGroupBatch['status'] === 'success' && $singleGroupBatch['data'][0]['data'] === [] && $restrictedGroupCalls === 3);
$mixedBatch = $queryClient->queryBatch([
    $entry($resolutionContext, ['queries' => ['one'], 'from' => $from, 'until' => $until]),
    $entry($resolutionContext, ['from' => $from, 'until' => $until, 'page' => 1]),
    $entry($resolutionContext, ['search' => 'sample', 'page' => 1]),
    $entry($resolutionContext, ['id' => 'one']),
])->get();
assert($mixedBatch['status'] === 'success' && count($mixedBatch['data']) === 4
    && $mixedBatch['data'][0]['data'][0]->resourceId === 'one'
    && $mixedBatch['data'][1]['page']['number'] === 1 && $mixedBatch['data'][2]['page']['number'] === 1
    && $mixedBatch['data'][3]['data'][0]->resourceId === 'one');

// Existing and standard exact reads preserve the same protected values.
$protectedWire = [];
$protectedAt = $boundedResolutionContext->asOf;
$protectedReference = new ResolvedResourceReference('sample-owner', 'sample-owner.worker', 'worker', 'Synthetic', null, [], $protectedAt,
    protectedValues: ['synthetic' => 'private-fixture']);
$protectedSnapshot = [...$protectedReference->snapshot(), 'protected_values' => $protectedReference->protectedValues()];
$protectedClient = new \Nexia\ResourceReference\TransportResourceReferences(static function (array $body) use (&$protectedWire, $protectedSnapshot): mixed {
    $protectedWire[] = $body;
    return $body['method'] === 'resolve' ? $protectedSnapshot : ['worker' => $protectedSnapshot];
});
$legacyOneProtected = $protectedClient->resolve('sample-owner.worker', 'worker', $boundedResolutionContext);
$legacyManyProtected = $protectedClient->resolveMany('sample-owner.worker', ['worker'], $boundedResolutionContext);
assert($legacyOneProtected->hasProtectedValues() && $legacyManyProtected['worker']->hasProtectedValues());
$protectedWire = [];
$oneProtected = $protectedClient->query('sample-owner.worker', $boundedResolutionContext)->where('id', 'worker')->allowLegacyContextFilters()->get();
$manyProtected = $protectedClient->query('sample-owner.worker', $boundedResolutionContext)->whereIn('id', ['worker'])->allowLegacyContextFilters()->get();
assert($oneProtected['data'][0]->hasProtectedValues() && $manyProtected['data'][0]->hasProtectedValues());
assert($oneProtected['data'][0]->protectedValues() === $manyProtected['data'][0]->protectedValues());
assert($oneProtected['data'][0]->protectedValues() === $legacyOneProtected->protectedValues());
assert(($protectedWire[0]['context']['include_protected_values'] ?? null) === true && count($protectedWire) === 2);
assert(! str_contains(json_encode($oneProtected['data'][0]->snapshot(), JSON_THROW_ON_ERROR), 'private-fixture'));
$unexpectedProtected = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $protectedSnapshot);
try { $unexpectedProtected->resolve('sample-owner.worker', 'worker', $resolutionContext); throw new LogicException('Unrequested protected values accepted.'); }
catch (RuntimeException $error) { assert($error->getMessage() === 'Invalid SDK resource snapshot.'); }
foreach (['invalid', [...$protectedSnapshot, 'protected_values' => null], [...$protectedSnapshot, 'protectedValues' => []]] as $invalidProtected) {
    $invalidClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $invalidProtected);
    assert($invalidClient->query('sample-owner.worker', $boundedResolutionContext)->where('id', 'worker')->allowLegacyContextFilters()->get()['status'] === 'error');
}
$extraClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => ['other' => $protectedSnapshot]);
assert($extraClient->query('sample-owner.worker', $boundedResolutionContext)->whereIn('id', ['worker'])->allowLegacyContextFilters()->get()['status'] === 'error');
$wrongClient = new \Nexia\ResourceReference\TransportResourceReferences(static fn () => $protectedSnapshot);
assert($wrongClient->query('sample-owner.worker', $boundedResolutionContext)->where('id', 'other')->allowLegacyContextFilters()->get()['status'] === 'error');
$legacyAttempts = 0;
$legacyHost = new \Nexia\ResourceReference\TransportResourceReferences(static function () use (&$legacyAttempts): never {
    $legacyAttempts++;
    throw new \Symfony\Component\HttpKernel\Exception\HttpException(422);
});
$result = $legacyHost->query('sample-owner.worker', $boundedResolutionContext)->where('id', 'worker')->allowLegacyContextFilters()->get();
assert($result['error']['code'] === 'INVALID_QUERY' && $legacyAttempts === 1);
echo "Protected standard and existing exact reads passed.\n";
