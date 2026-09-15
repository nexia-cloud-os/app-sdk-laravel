<?php

declare(strict_types=1);

namespace Nexia\Tests\Fixtures\OperationsFixture\Contribution\Resources;

use Nexia\AppDescriptors\DescriptorStatus;
use Nexia\AppDescriptors\ResourceDescriptor;
use Nexia\AppRuntime\Concerns\HasShellResource;
use Nexia\AppRuntime\Contracts\ShellResourceContribution;
use Nexia\Contribution\AbstractResourceModule;
use Nexia\Contribution\ResourceAuthorizationContract;
use Nexia\Contribution\Contracts\ResourceAuthorizationContribution;
use Nexia\Contribution\Contracts\ResourceCatalogContribution;
use Nexia\Contribution\ResourceLegalEntityParticipation;
use Nexia\Contribution\ResourceModuleDefinition;
use Nexia\Contribution\ResourceRecordOwner;
use Nexia\Navigation\Concerns\ContributesNavigationDestination;
use Nexia\Navigation\Contracts\NavigationContribution;
use Nexia\Permission\AssignmentScope;
use Nexia\Permission\Concerns\ContributesResourcePermissions;
use Nexia\Permission\Contracts\PermissionContribution;
use Nexia\Tests\Fixtures\OperationsFixture\Enums\WorkTicketStatus;
use Nexia\Tests\Fixtures\OperationsFixture\Models\WorkTicket;

final class WorkTicketModule extends AbstractResourceModule implements NavigationContribution, PermissionContribution, ResourceAuthorizationContribution, ResourceCatalogContribution, ShellResourceContribution
{
    use ContributesNavigationDestination;
    use ContributesResourcePermissions;
    use HasShellResource;

    protected static array $navigation = [
        'icon' => 'ticket',
        'order' => 10,
        'context' => 'app',
        'group' => 'operations',
        'visible' => true,
    ];

    public static function resourceKey(): string
    {
        return 'operations-fixture.work_ticket';
    }

    public static function resourceModelClass(): string
    {
        return WorkTicket::class;
    }

    public static function permissionAssignmentScope(): AssignmentScope
    {
        return AssignmentScope::LegalEntity;
    }

    public static function resourceAuthorization(): ResourceAuthorizationContract
    {
        return ResourceAuthorizationContract::standard(
            ResourceRecordOwner::LegalEntity,
            AssignmentScope::LegalEntity,
            ResourceLegalEntityParticipation::RecordOwner,
        );
    }

    public static function permissionResources(): array
    {
        return ['operations-fixture.work_ticket' => ['read', 'send', 'record_result', 'cancel']];
    }

    public static function resourceModuleDefinition(): ResourceModuleDefinition
    {
        return ResourceModuleDefinition::fromDescriptor(new ResourceDescriptor(
            key: self::resourceKey(),
            version: '1.0',
            status: DescriptorStatus::Active,
            fieldSchema: [
                'public_id' => ['type' => 'string'],
                'status' => [
                    'type' => 'string',
                    'enum' => array_map(static fn (WorkTicketStatus $status): string => $status->value, WorkTicketStatus::cases()),
                ],
            ],
            searchSchema: ['label_fields' => ['public_id']],
        ));
    }
}
