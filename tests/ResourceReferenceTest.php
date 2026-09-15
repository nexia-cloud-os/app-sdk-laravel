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

fwrite(STDOUT, "Resource reference contract passed.\n");
