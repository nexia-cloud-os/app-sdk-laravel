<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;
use Nexia\Contribution\Contracts\ResourceAuthorizationContribution;
use Nexia\Contribution\Contracts\ResourceCatalogContribution;
use Nexia\Contribution\ResourceModuleDefinition;
use Nexia\Laravel\StateMachine\EnumStateTransition;
use Nexia\Tests\Fixtures\OperationsFixture\Contribution\Resources\WorkTicketModule;
use Nexia\Tests\Fixtures\OperationsFixture\Enums\WorkTicketStatus;

require __DIR__.'/register-source-autoload.php';

$resources = WorkTicketModule::appDescriptors()->all();

if (! is_subclass_of(WorkTicketModule::class, AppDescriptorContribution::class)
    || ! is_subclass_of(WorkTicketModule::class, ResourceCatalogContribution::class)
    || ! is_subclass_of(WorkTicketModule::class, ResourceAuthorizationContribution::class)
    || count($resources) !== 1
    || $resources[0]->key !== WorkTicketModule::resourceKey()
) {
    throw new RuntimeException('Resource Module contracts changed unexpectedly.');
}

$privateResource = ResourceModuleDefinition::withoutDescriptor(
    'fixture.private_resource',
    'fixture.private_resource.label',
);
if ($privateResource->publicDescriptor() !== null
    || $privateResource->labelKey() !== 'fixture.private_resource.label'
) {
    throw new RuntimeException('A private Resource Module must expose its canonical label without publishing a descriptor.');
}

try {
    ResourceModuleDefinition::withoutDescriptor('fixture.blank_resource', ' ');
    throw new RuntimeException('A Resource Module accepted a blank label key.');
} catch (InvalidArgumentException) {
    // Expected definition validation.
}

$send = EnumStateTransition::to(
    WorkTicketStatus::Sent,
    [WorkTicketStatus::Draft],
);

if ($send->resolve(WorkTicketStatus::Draft) !== WorkTicketStatus::Sent
    || $send->resolve('DRAFT') !== WorkTicketStatus::Sent) {
    throw new RuntimeException('Enum transition resolution changed unexpectedly.');
}

$recordable = EnumStateTransition::guard([WorkTicketStatus::Sent]);
$recordable->assertAllowed(WorkTicketStatus::Sent);
EnumStateTransition::stateless()->assertAllowed(null);

try {
    $send->resolve(WorkTicketStatus::Recorded, 'status');
    throw new RuntimeException('An invalid enum transition was accepted.');
} catch (ValidationException $exception) {
    if (! isset($exception->errors()['status'][0])) {
        throw new RuntimeException('Invalid transitions must use the standard validation error shape.');
    }
}

try {
    EnumStateTransition::to(WorkTicketStatus::Cancelled, []);
    throw new RuntimeException('An empty transition source set was accepted.');
} catch (InvalidArgumentException) {
    // Expected definition validation.
}

fwrite(STDOUT, "Resource Module contracts passed.\n");
