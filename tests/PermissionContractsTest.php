<?php

declare(strict_types=1);

use Nexia\Contribution\ResourceAuthorizationContract;
use Nexia\Contribution\Contracts\ResourceAuthorizationContribution;
use Nexia\Contribution\ResourceLegalEntityParticipation;
use Nexia\Contribution\ResourceRecordOwner;
use Nexia\Contribution\ResourceVisibilityProfile;
use Nexia\Permission\AssignmentScope;
use Nexia\Permission\GrantControl;
use Nexia\Permission\Contracts\PermissionContribution;
use Nexia\Permission\PermissionDefinition;
use Nexia\Permission\PresetType;
use Nexia\Permission\Contracts\RolePresetContribution;

require dirname(__DIR__).'/vendor/autoload.php';

$definition = PermissionDefinition::make(
    appKey: 'sample',
    resource: 'expense_report',
    action: 'approve',
    assignmentScope: AssignmentScope::OperatingUnit,
    grantControl: GrantControl::Protected,
);

if ($definition['key'] !== 'sample.expense_report.approve'
    || $definition['assignment_scope'] !== 'operating_unit'
    || $definition['grant_control'] !== 'protected'
    || PresetType::Capability->value !== 'capability'
    || ! AssignmentScope::OperatingUnit->supportsGrantAt(AssignmentScope::LegalEntity)
    || AssignmentScope::LegalEntity->supportsGrantAt(AssignmentScope::OperatingUnit)
) {
    throw new RuntimeException('Permission contracts changed unexpectedly.');
}

$authorization = ResourceAuthorizationContract::standard(
    ResourceRecordOwner::LegalEntity,
    AssignmentScope::LegalEntity,
    ResourceLegalEntityParticipation::RecordOwner,
);

if ($authorization->visibilityProfile !== ResourceVisibilityProfile::Standard) {
    throw new RuntimeException('Resource authorization contract changed unexpectedly.');
}

$contributor = new class implements PermissionContribution, ResourceAuthorizationContribution, RolePresetContribution
{
    public static function resourceKey(): string
    {
        return 'sample.expense_report';
    }

    public static function resourceModelClass(): string
    {
        return stdClass::class;
    }

    public static function resourceAuthorization(): ResourceAuthorizationContract
    {
        return ResourceAuthorizationContract::standard(
            ResourceRecordOwner::Tenant,
            AssignmentScope::Tenant,
            ResourceLegalEntityParticipation::None,
        );
    }

    public static function catalogPermissionDefinitions(): array
    {
        return [];
    }

    public static function rolePresets(): array
    {
        return [];
    }
};

if ($contributor::resourceAuthorization()->recordOwner !== ResourceRecordOwner::Tenant) {
    throw new RuntimeException('Contribution contracts changed unexpectedly.');
}

fwrite(STDOUT, "Permission contracts passed.\n");
