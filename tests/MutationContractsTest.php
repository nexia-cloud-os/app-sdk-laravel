<?php

declare(strict_types=1);

use Nexia\Mutation\Contracts\MutationPublisher;
use Nexia\Mutation\MutationMatcher;
use Nexia\Mutation\MutationOperation;

require dirname(__DIR__).'/vendor/autoload.php';

$resource = new MutationMatcher(
    resourceKeys: ['sample-owner.worker'],
    operations: [MutationOperation::Created, MutationOperation::Updated],
    resourceIds: ['worker-1'],
);
$action = new MutationMatcher(
    actionKeys: ['sample-owner.worker.merge'],
    operations: [MutationOperation::Succeeded],
);

if ($resource->resourceIds !== ['worker-1']
    || $action->operations !== [MutationOperation::Succeeded]) {
    throw new RuntimeException('Mutation matcher contracts changed unexpectedly.');
}

$publisher = new class implements MutationPublisher
{
    /** @var list<array{kind: string, key: string, operation: string, resource_id: ?string}> */
    public array $published = [];

    public function resourceChanged(
        string $resourceKey,
        MutationOperation $operation,
        ?string $resourceId = null,
    ): void {
        $this->published[] = [
            'kind' => 'resource',
            'key' => $resourceKey,
            'operation' => $operation->value,
            'resource_id' => $resourceId,
        ];
    }

    public function actionSucceeded(string $actionKey): void
    {
        $this->published[] = [
            'kind' => 'action',
            'key' => $actionKey,
            'operation' => MutationOperation::Succeeded->value,
            'resource_id' => null,
        ];
    }
};

$publisher->resourceChanged('sample-owner.worker', MutationOperation::Updated, 'worker-1');
$publisher->actionSucceeded('sample-owner.worker.merge');

if ($publisher->published !== [
    [
        'kind' => 'resource',
        'key' => 'sample-owner.worker',
        'operation' => 'updated',
        'resource_id' => 'worker-1',
    ],
    [
        'kind' => 'action',
        'key' => 'sample-owner.worker.merge',
        'operation' => 'succeeded',
        'resource_id' => null,
    ],
]) {
    throw new RuntimeException('Mutation publisher contract changed unexpectedly.');
}

$invalid = [
    static fn () => new MutationMatcher,
    static fn () => new MutationMatcher(resourceKeys: ['sample-owner.worker']),
    static fn () => new MutationMatcher(
        resourceKeys: ['sample-owner.worker'],
        actionKeys: ['sample-owner.worker.merge'],
        operations: [MutationOperation::Updated],
    ),
    static fn () => new MutationMatcher(
        resourceKeys: ['sample-owner.worker'],
        operations: [MutationOperation::Succeeded],
    ),
    static fn () => new MutationMatcher(
        actionKeys: ['sample-owner.worker.merge'],
        operations: [MutationOperation::Updated],
    ),
    static fn () => new MutationMatcher(
        actionKeys: ['sample-owner.worker.merge'],
        operations: [MutationOperation::Succeeded],
        resourceIds: ['worker-1'],
    ),
    static fn () => new MutationMatcher(
        resourceKeys: ['sample-owner.worker', 'sample-owner.worker'],
        operations: [MutationOperation::Updated],
    ),
    static fn () => new MutationMatcher(
        resourceKeys: ['sample-owner.worker'],
        operations: [MutationOperation::Updated],
        resourceIds: [''],
    ),
    static fn () => new MutationMatcher(
        resourceKeys: [42],
        operations: [MutationOperation::Updated],
    ),
    static fn () => new MutationMatcher(
        actionKeys: ['sample-owner.'.str_repeat('a', 154)],
        operations: [MutationOperation::Succeeded],
    ),
];

foreach ($invalid as $case) {
    try {
        $case();
        throw new RuntimeException('Invalid mutation matcher was accepted.');
    } catch (InvalidArgumentException) {
        // Expected.
    }
}

fwrite(STDOUT, "Mutation contracts passed.\n");
