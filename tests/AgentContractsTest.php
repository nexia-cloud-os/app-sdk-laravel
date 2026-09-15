<?php

declare(strict_types=1);

use Nexia\Agent\Contracts\AgentToolContribution;
use Nexia\Agent\AgentToolDeclaration;
use Nexia\Agent\AgentToolEffect;
use Nexia\Agent\AgentToolExecutor;
use Nexia\Agent\AgentToolLane;
use Nexia\Agent\AgentToolRouting;
use Nexia\Agent\AgentToolSurface;
use Nexia\Agent\AgentToolTier;

require dirname(__DIR__).'/vendor/autoload.php';

final class AgentContractsFixture implements AgentToolContribution
{
    /** @return list<AgentToolDeclaration> */
    public static function agentTools(): array
    {
        return [new AgentToolDeclaration(
            key: 'sample-time.personal_time_leave.read',
            tier: AgentToolTier::Auto,
            routing: new AgentToolRouting(
                lanes: [AgentToolLane::DirectData, AgentToolLane::Dashboard],
                effect: AgentToolEffect::Read,
                surface: AgentToolSurface::Data,
                preconditions: ['legal_entity_selected'],
            ),
            description: 'Read the current user personal leave balance.',
            method: 'GET',
            path: '/api/legal-entities/{legalEntity:public_id}/sample-time/personal-leave',
            permissions: ['sample-time.leave_balance.read'],
            inputSchema: [
                'type' => 'object',
                'properties' => ['as_of' => ['type' => 'string']],
                'additionalProperties' => false,
            ],
            invalidatedModels: [stdClass::class],
        )];
    }
}

$tool = AgentContractsFixture::agentTools()[0];
$row = $tool->toArray();

if ($row['key'] !== 'sample-time.personal_time_leave.read'
    || $row['executor'] !== AgentToolExecutor::Laravel->value
    || $row['tier'] !== AgentToolTier::Auto->value
    || $row['routing'] !== [
        'lanes' => ['direct_data', 'dashboard'],
        'effect' => 'read',
        'surface' => 'data',
        'preconditions' => ['legal_entity_selected'],
    ]
    || $row['http'] !== [
        'method' => 'GET',
        'path' => '/api/legal-entities/{legalEntity:public_id}/sample-time/personal-leave',
    ]
    || $row['permissions'] !== ['sample-time.leave_balance.read']
    || $row['input_schema']['type'] !== 'object'
    || $tool->invalidatedModels !== [stdClass::class]
) {
    throw new RuntimeException('Agent declaration wire contract changed unexpectedly.');
}

$external = new AgentToolDeclaration(
    key: 'sample-review.inspection.share',
    tier: AgentToolTier::Confirm,
    routing: AgentToolRouting::external(
        AgentToolLane::DirectData,
        AgentToolSurface::Web,
    ),
    description: 'Share an inspection result with an external recipient.',
    method: 'POST',
    path: '/api/sample-review/inspections/{inspection}/share',
    permissions: ['sample-review.inspection.share'],
    facets: [AgentToolDeclaration::FACET_EXTERNAL_SHARE],
);

if ($external->routing->effect !== AgentToolEffect::External
    || $external->toArray()['facets'] !== [AgentToolDeclaration::FACET_EXTERNAL_SHARE]
) {
    throw new RuntimeException('External Agent declaration contract changed unexpectedly.');
}

$invalidFactories = [
    static fn () => new AgentToolRouting(
        lanes: [],
        effect: AgentToolEffect::Read,
    ),
    static fn () => new AgentToolRouting(
        lanes: ['primary' => AgentToolLane::DirectData],
        effect: AgentToolEffect::Read,
    ),
    static fn () => new AgentToolRouting(
        lanes: [AgentToolLane::DirectData],
        effect: AgentToolEffect::Read,
        preconditions: ['selected' => 'legal_entity_selected'],
    ),
    static fn () => new AgentToolDeclaration(
        key: 'sample-time.personal_time_leave.read',
        tier: AgentToolTier::Auto,
        routing: AgentToolRouting::read(AgentToolLane::DirectData),
        description: 'Invalid list-shaped input schema.',
        method: 'GET',
        path: '/api/sample-time/personal-leave',
        permissions: ['sample-time.leave_balance.read'],
        inputSchema: [],
    ),
    static fn () => new AgentToolDeclaration(
        key: 'sample-time.personal-time-leave.read',
        tier: AgentToolTier::Auto,
        routing: AgentToolRouting::read(AgentToolLane::DirectData),
        description: 'Only the App namespace may use kebab-case.',
        method: 'GET',
        path: '/api/sample-time/personal-leave',
        permissions: ['sample-time.leave_balance.read'],
    ),
    static fn () => new AgentToolDeclaration(
        key: 'sample-review.inspection.share',
        tier: AgentToolTier::Confirm,
        routing: AgentToolRouting::external(
            AgentToolLane::DirectData,
            AgentToolSurface::Web,
        ),
        description: 'External effects require an explicit facet.',
        method: 'POST',
        path: '/api/sample-review/inspections/{inspection}/share',
        permissions: ['sample-review.inspection.share'],
    ),
    static fn () => new AgentToolDeclaration(
        key: 'sample-review.inspection.update',
        tier: AgentToolTier::Confirm,
        routing: AgentToolRouting::mutate(
            AgentToolLane::DirectData,
            AgentToolSurface::Data,
        ),
        description: 'Internal mutations cannot claim the external-share facet.',
        method: 'PATCH',
        path: '/api/sample-review/inspections/{inspection}',
        permissions: ['sample-review.inspection.update'],
        facets: [AgentToolDeclaration::FACET_EXTERNAL_SHARE],
    ),
    static fn () => new AgentToolDeclaration(
        key: 'sample-review.inspection.share',
        tier: AgentToolTier::Auto,
        routing: AgentToolRouting::external(
            AgentToolLane::DirectData,
            AgentToolSurface::Web,
        ),
        description: 'External-share declarations cannot default below confirm.',
        method: 'POST',
        path: '/api/sample-review/inspections/{inspection}/share',
        permissions: ['sample-review.inspection.share'],
        facets: [AgentToolDeclaration::FACET_EXTERNAL_SHARE],
    ),
];

foreach ($invalidFactories as $factory) {
    try {
        $factory();
    } catch (InvalidArgumentException) {
        continue;
    }

    throw new RuntimeException('Invalid Agent SDK declaration was accepted.');
}

fwrite(STDOUT, "Agent contracts passed.\n");
