<?php

declare(strict_types=1);

use Nexia\Approval\Domain\ApprovalException;
use Nexia\Approval\Domain\ApprovalLineDefinition;
use Nexia\Approval\Domain\ApprovalLineStepDefinition;
use Nexia\Approval\Resolver\ResolutionError;
use Nexia\Approval\Resolver\ResolutionErrorCode;
use Nexia\Approval\Resolver\ResolvedApprovalLine;

require dirname(__DIR__).'/vendor/autoload.php';

$line = ApprovalLineDefinition::fromStages([
    [ApprovalLineStepDefinition::approver(10)],
    [ApprovalLineStepDefinition::consultant(20), ApprovalLineStepDefinition::approver(30, true)],
]);

if ($line->toSnapshot() !== [
    [
        'position' => 1,
        'steps' => [[
            'kind' => 'approval',
            'user_id' => 10,
            'is_final_authority' => false,
        ]],
    ],
    [
        'position' => 2,
        'steps' => [
            [
                'kind' => 'consultation',
                'user_id' => 20,
                'is_final_authority' => false,
            ],
            [
                'kind' => 'approval',
                'user_id' => 30,
                'is_final_authority' => true,
            ],
        ],
    ],
]) {
    throw new RuntimeException('Approval line snapshot contract changed unexpectedly.');
}

$invalidCases = [
    static fn (): ApprovalLineDefinition => ApprovalLineDefinition::fromStages([]),
    static fn (): ApprovalLineDefinition => ApprovalLineDefinition::fromStages([[]]),
    static fn (): ApprovalLineDefinition => ApprovalLineDefinition::fromStages([
        [ApprovalLineStepDefinition::approver(10)],
        [ApprovalLineStepDefinition::consultant(10)],
    ]),
    static fn (): ApprovalLineDefinition => ApprovalLineDefinition::fromStages([
        [ApprovalLineStepDefinition::consultant(10)],
    ]),
];

foreach ($invalidCases as $case) {
    try {
        $case();
    } catch (ApprovalException) {
        continue;
    }

    throw new RuntimeException('Invalid approval line was accepted.');
}

try {
    ApprovalLineStepDefinition::consultant(0);
    throw new RuntimeException('Invalid approval actor id was accepted.');
} catch (ApprovalException) {
}

try {
    new ApprovalLineStepDefinition(
        Nexia\Approval\Domain\Enums\ApprovalStepKind::Consultation,
        10,
        true,
    );
    throw new RuntimeException('Consultation step accepted final authority.');
} catch (ApprovalException) {
}

$error = ApprovalException::operationFailed('Diagnostic', 'stable_code', ['field' => 'safe'], 422);

if ($error->getCode() !== 422 || $error->toErrorPayload() !== [
    'code' => 'stable_code',
    'params' => ['field' => 'safe'],
]) {
    throw new RuntimeException('Approval exception payload contract changed unexpectedly.');
}

$resolved = ResolvedApprovalLine::resolved([[ApprovalLineStepDefinition::approver(10)]]);
$failed = ResolvedApprovalLine::failed([
    new ResolutionError(ResolutionErrorCode::ResolverUnavailable, 'Unavailable', reason: 'test.unavailable'),
]);

if (! $resolved->isResolved()
    || $resolved->stages()[0][0]->userId !== 10
    || $failed->isResolved()
    || $failed->firstError()?->category() !== 'availability') {
    throw new RuntimeException('SDK approval resolver result contract changed unexpectedly.');
}

fwrite(STDOUT, "Approval line contracts passed.\n");
