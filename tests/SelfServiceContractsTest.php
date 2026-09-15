<?php

declare(strict_types=1);

use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;
use Nexia\SelfService\SelfServiceActionItem;
use Nexia\SelfService\SelfServiceActionStage;
use Nexia\SelfService\SelfServiceReturnTarget;
use Nexia\SelfService\SelfWorkContextOption;

require dirname(__DIR__).'/vendor/autoload.php';

$resource = new ResourceRef(
    appKey: 'sample-owner',
    resourceKey: 'sample-owner.record',
    resourceId: 'internal-record-reference',
    display: 'Sample record',
);
$context = new SelfWorkContextOption(
    resource: $resource,
    legalEntity: new class implements LegalEntity
    {
        public function key(): int|string
        {
            return 1;
        }

        public function publicId(): string
        {
            return '00000000-0000-0000-0000-000000000001';
        }

        public function displayLabel(): string
        {
            return 'Nexia Korea';
        }

        public function code(): string
        {
            return 'NXKR';
        }

        public function partyPublicId(): ?string
        {
            return null;
        }

        public function isActiveOrganization(): bool
        {
            return true;
        }
    },
    label: 'Nexia Korea · Regular',
    description: 'Member 1001',
    status: 'Active',
    effectiveFrom: '2026-01-01',
    isPrimary: true,
);
$action = new SelfServiceActionItem(
    key: 'sample-owner.action.current',
    category: 'Sample',
    title: 'Continue action',
    summary: 'Two items remain',
    stage: SelfServiceActionStage::ActionRequired,
    returnTarget: new SelfServiceReturnTarget(
        tab: 'sample-destination',
        view: 'sample-view',
        workContext: $resource,
    ),
    priority: 90,
);

if (! $context->isPrimary
    || $action->returnTarget->workContext !== $resource
    || $action->stage !== SelfServiceActionStage::ActionRequired) {
    throw new RuntimeException('Self-service contracts changed unexpectedly.');
}

if ((new SelfServiceReturnTarget(tab: 'sample-destination'))->tab !== 'sample-destination') {
    throw new RuntimeException('Return targets must accept canonical host-owned Profile destinations.');
}

foreach (['Admin', 'sample.destination', '-sample', 'sample destination'] as $invalidDestination) {
    try {
        new SelfServiceReturnTarget(tab: $invalidDestination);
        throw new RuntimeException('Return targets must reject non-canonical Profile destinations.');
    } catch (InvalidArgumentException) {
        // Expected validation failure.
    }
}

try {
    new SelfServiceActionItem(
        key: 'invalid',
        category: 'Sample',
        title: 'Continue action',
        summary: 'Two items remain',
        stage: SelfServiceActionStage::ActionRequired,
        returnTarget: new SelfServiceReturnTarget('sample-destination'),
    );
    throw new RuntimeException('Action items must reject non-namespaced keys.');
} catch (InvalidArgumentException) {
    // Expected validation failure.
}

fwrite(STDOUT, "Self-service contracts passed.\n");
