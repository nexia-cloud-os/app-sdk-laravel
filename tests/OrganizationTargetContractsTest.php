<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Nexia\Laravel\Access\Contracts\OrganizationTargetResolver;
use Nexia\Laravel\Http\OrganizationTargetQueryRequest;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;
use Nexia\Organization\OrganizationTarget;
use Nexia\Organization\OrganizationTargetQuery;
use Nexia\Organization\OrganizationTargetSet;

require dirname(__DIR__).'/vendor/autoload.php';

$container = new Container;
$container->instance('validator', new Factory(new Translator(new ArrayLoader, 'en'), $container));
Facade::setFacadeApplication($container);

$legalEntity = new class implements LegalEntity
{
    public function key(): int|string { return 1; }
    public function publicId(): string { return 'C0A80101-0000-4000-8000-000000000001'; }
    public function displayLabel(): string { return 'Headquarters'; }
    public function code(): string { return 'HQ'; }
    public function partyPublicId(): ?string { return null; }
    public function isActiveOrganization(): bool { return true; }
};
$operatingUnit = new class implements OperatingUnit
{
    public function key(): int|string { return 2; }
    public function publicId(): string { return 'C0A80101-0000-4000-8000-000000000002'; }
    public function displayLabel(): string { return 'Operations'; }
    public function parentKey(): int|string|null { return null; }
    public function code(): string { return 'OPS'; }
    public function classification(): string { return 'department'; }
    public function accentColor(): ?string { return null; }
};

$query = OrganizationTargetQuery::fromInput([
    'legal_entity_public_ids' => [
        'C0A80101-0000-4000-8000-000000000003',
        'c0a80101-0000-4000-8000-000000000001',
        'c0a80101-0000-7000-8000-000000000007',
        'c0a80101-0000-8000-8000-000000000008',
    ],
    'operating_unit_public_ids' => ['c0a80101-0000-4000-8000-000000000002'],
]);
if ($query->legalEntityPublicIds !== [
    'c0a80101-0000-4000-8000-000000000001',
    'c0a80101-0000-4000-8000-000000000003',
    'c0a80101-0000-7000-8000-000000000007',
    'c0a80101-0000-8000-8000-000000000008',
]
    || $query->operatingUnitPublicIds !== ['c0a80101-0000-4000-8000-000000000002']) {
    throw new RuntimeException('Organization target query canonicalization changed unexpectedly.');
}

foreach ([
    ['legal_entity_public_ids' => []],
    ['legal_entity_public_ids' => 'c0a80101-0000-4000-8000-000000000001'],
    ['legal_entity_public_ids' => ['c0a80101-0000-4000-8000-000000000001', 'C0A80101-0000-4000-8000-000000000001']],
    ['legal_entity_public_ids' => [' c0a80101-0000-4000-8000-000000000001']],
    ['legal_entity_public_id' => 'c0a80101-0000-4000-8000-000000000001'],
    ['legal_entity_public_id[]' => ['c0a80101-0000-4000-8000-000000000001']],
    ['operating_unit_public_id' => ['c0a80101-0000-4000-8000-000000000002']],
    ['operating_unit_public_id[]' => ['c0a80101-0000-4000-8000-000000000002']],
    ['legal_entity_public_ids[]' => ['c0a80101-0000-4000-8000-000000000001']],
    ['operating_unit_public_ids[]' => ['c0a80101-0000-4000-8000-000000000002']],
    ['operating_unit_public_ids' => [' ']],
    ['operating_unit_public_ids' => ['not-a-uuid']],
] as $input) {
    try {
        OrganizationTargetQuery::fromInput($input);
        throw new RuntimeException('Invalid organization target query was accepted.');
    } catch (InvalidArgumentException) {
    }
}

$targets = new OrganizationTargetSet([
    new OrganizationTarget($legalEntity),
    new OrganizationTarget($legalEntity, $operatingUnit),
]);
if (count($targets->filter($query)->targets) !== 1
    || $targets->filter($query)->targets[0]->legalEntity !== $legalEntity
    || $targets->filter($query)->targets[0]->operatingUnit !== $operatingUnit) {
    throw new RuntimeException('Organization target filtering must retain exact authorized pairs only.');
}

if (! interface_exists(OrganizationTargetResolver::class)
    || ! method_exists(OrganizationTargetResolver::class, 'resolveListTargets')
    || ! method_exists(OrganizationTargetResolver::class, 'resolveCreateTarget')) {
    throw new RuntimeException('Organization target host resolver contract is missing.');
}

try {
    OrganizationTargetQueryRequest::from(Request::create('/', 'GET', [
        'legal_entity_public_ids' => 'c0a80101-0000-4000-8000-000000000001',
    ]));
    throw new RuntimeException('Scalar organization target query was accepted by the Laravel adapter.');
} catch (ValidationException $exception) {
    if (! array_key_exists('legal_entity_public_ids', $exception->errors())) {
        throw new RuntimeException('Organization target query errors must identify the invalid field.');
    }
}

foreach ([
    ['/?legal_entity_public_id=c0a80101-0000-4000-8000-000000000001', 'legal_entity_public_id'],
    ['/?legal_entity_public_id%5B%5D=c0a80101-0000-4000-8000-000000000001', 'legal_entity_public_id'],
    ['/?operating_unit_public_id=c0a80101-0000-4000-8000-000000000002', 'operating_unit_public_id'],
    ['/?operating_unit_public_id%5B%5D=c0a80101-0000-4000-8000-000000000002', 'operating_unit_public_id'],
    ['/?legal_entity_public_ids=c0a80101-0000-4000-8000-000000000001', 'legal_entity_public_ids'],
    ['/?operating_unit_public_ids=c0a80101-0000-4000-8000-000000000002', 'operating_unit_public_ids'],
] as [$url, $field]) {
    try {
        OrganizationTargetQueryRequest::from(Request::create($url, 'GET'));
        throw new RuntimeException("Unsupported organization target URL [{$url}] was accepted by the Laravel adapter.");
    } catch (ValidationException $exception) {
        if (! array_key_exists($field, $exception->errors())) {
            throw new RuntimeException("Unsupported organization target URL errors must identify [{$field}].");
        }
    }
}

$unrelatedRequestQuery = OrganizationTargetQueryRequest::from(Request::create('/?page=2&sort=updated_at', 'GET'));
if (! $unrelatedRequestQuery->isUnrestricted()) {
    throw new RuntimeException('Unrelated query parameters must not constrain organization targets.');
}

$canonicalRequestQuery = OrganizationTargetQueryRequest::from(Request::create(
    '/?legal_entity_public_ids%5B%5D=C0A80101-0000-4000-8000-000000000001&legal_entity_public_ids%5B%5D=c0a80101-0000-7000-8000-000000000007',
    'GET',
));
if ($canonicalRequestQuery->legalEntityPublicIds !== [
    'c0a80101-0000-4000-8000-000000000001',
    'c0a80101-0000-7000-8000-000000000007',
]) {
    throw new RuntimeException('Laravel must expose canonical bracketed organization target URL keys as an array.');
}

try {
    OrganizationTargetQueryRequest::from(Request::create('/', 'GET', [
        'legal_entity_public_ids' => [
            'c0a80101-0000-4000-8000-000000000001',
            'C0A80101-0000-4000-8000-000000000001',
        ],
    ]));
    throw new RuntimeException('Duplicate organization target query was accepted by the Laravel adapter.');
} catch (ValidationException $exception) {
    if (! array_key_exists('legal_entity_public_ids', $exception->errors())) {
        throw new RuntimeException('Duplicate organization target errors must identify the invalid field.');
    }
}

fwrite(STDOUT, "Organization target contracts passed.\n");
