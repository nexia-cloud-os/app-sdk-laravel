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
