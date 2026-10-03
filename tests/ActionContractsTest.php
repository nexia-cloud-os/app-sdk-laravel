<?php

declare(strict_types=1);

use Nexia\Actions\ActionDefinition;
use Nexia\Actions\ActionPlacement;
use Nexia\Actions\ActionTargets;

require dirname(__DIR__).'/vendor/autoload.php';

$recordAction = new ActionDefinition('approve', 'sample.record.approve', 'POST', '/api/sample/{id}/approve');
assert($recordAction->placements === [ActionPlacement::Agent]);
$global = new ActionDefinition('sample.refresh', 'sample.refresh', 'POST', '/api/sample/refresh', labelKey: 'sample.refresh.label', targets: ActionTargets::None, placements: [ActionPlacement::Dashboard]);
assert($global->targets === ActionTargets::None);
assert(ActionDefinition::fromArray($global->toArray())->toArray() === $global->toArray());
assert(ActionDefinition::fromArray($recordAction->toArray())->toArray() === $recordAction->toArray());
$tool = $recordAction->agentTool('sample.record.approve');
assert($tool->tier === \Nexia\Agent\AgentToolTier::Confirm);
assert($tool->permissions === ['sample.record.approve']);
assert($tool->path === $recordAction->path);
assert($tool->inputSchema['required'] === ['id']);
$bulk = new ActionDefinition('approve', 'sample.record.approve', 'POST', '/api/sample/approve', targets: ActionTargets::Many, targetParameter: 'ids');
assert($bulk->agentTool('sample.record.approve')->inputSchema['properties']['ids']['maxItems'] === 100);
try {
    $global->agentTool('sample.refresh');
    throw new RuntimeException('Human-only action was exposed to an Agent.');
} catch (InvalidArgumentException) {
}
foreach ([[], ['dashboard'], [ActionPlacement::Dashboard, ActionPlacement::Dashboard]] as $placements) {
    try {
        new ActionDefinition('approve', 'sample.record.approve', 'POST', '/api/sample/{id}/approve', placements: $placements);
        throw new RuntimeException('Invalid action placements accepted.');
    } catch (InvalidArgumentException) {
    }
}
echo "Action contracts passed.\n";
