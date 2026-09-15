<?php

declare(strict_types=1);

use Nexia\Events\ActorReference;
use Nexia\Events\ConsumerResult;
use Nexia\Events\Contracts\ActorDelegatedAppEventListeners;
use Nexia\Events\Contracts\AppEventListeners;
use Nexia\Events\Contracts\EventActorAuthorizer;
use Nexia\Events\Contracts\EventCurrentStateReconcilers;
use Nexia\Events\Contracts\EventPublisher;
use Nexia\Events\Contracts\EventReceiptPublisher;
use Nexia\Events\Contracts\InboxConsumer;
use Nexia\Events\Contracts\Outbox;
use Nexia\Events\EventConsumerRegistration;
use Nexia\Events\EventDraft;
use Nexia\Events\EventEnvelope;
use Nexia\Events\EventPublicationReceipt;
use Nexia\Events\EventRecoveryMode;
use Nexia\Events\EventSubscriptionMode;
use Nexia\Events\HandoffResultState;
use Nexia\Events\LegalEntityScope;
use Nexia\Identity\Contracts\Actor;
use Nexia\ResourceReference\ResourceProjectionChanged;

require dirname(__DIR__).'/vendor/autoload.php';

$envelope = new EventEnvelope(
    eventName: 'sample.expense_report.submitted',
    tenantId: 'tenant-test',
    payload: ['public_id' => 'report-1'],
    producerAppKey: 'sample',
    aggregateType: 'sample.expense_report',
    aggregateId: 'report-1',
    correlationId: 'correlation-1',
    occurredAt: '2026-07-28T00:00:00+00:00',
);

$roundTrip = EventEnvelope::fromArray($envelope->toArray());
if ($roundTrip->identity() !== $envelope->identity()
    || strlen($envelope->boundedIdentity()) > 36
    || method_exists(EventEnvelope::class, 'create')
) {
    throw new RuntimeException('Event envelope contract changed unexpectedly.');
}

$draft = new EventDraft(
    eventName: 'sample.expense_report.submitted',
    payload: ['public_id' => 'report-1'],
    legalEntityScope: LegalEntityScope::inherit(),
    actor: new ActorReference('actor-1', 'user'),
    aggregateType: 'sample.expense_report',
    aggregateId: 'report-1',
);
$resourceProjectionChanged = ResourceProjectionChanged::draft(
    appKey: 'sample',
    resourceKey: 'sample.expense_report',
    resourceId: 'report-1',
    legalEntityId: 7,
    correlationId: 'correlation-1',
    causationId: 'causation-1',
);
$published = new class implements EventPublisher
{
    public ?EventDraft $draft = null;

    public function publish(EventDraft $draft): void
    {
        $this->draft = $draft;
    }
};
$published->publish($draft);

$receiptPublisher = new class implements EventReceiptPublisher
{
    public function publish(EventDraft $draft): void {}

    public function publishWithReceipt(EventDraft $draft): EventPublicationReceipt
    {
        return new EventPublicationReceipt('publication-1');
    }
};

$currentStateReconcilers = new class implements EventCurrentStateReconcilers
{
    /** @var array<string, callable(): iterable<EventPublicationReceipt>> */
    private array $reconcilers = [];

    public function register(string $ownerAppKey, string $eventName, callable $reconciler): void
    {
        $this->reconcilers[$eventName] = $reconciler;
    }

    public function reconcile(string $eventName): int
    {
        return iterator_count(($this->reconcilers[$eventName])());
    }
};
$currentStateReconcilers->register(
    'sample',
    'sample.expense_report.submitted',
    static function (): iterable {
        yield new EventPublicationReceipt('publication-2');
    },
);

if ($published->draft !== $draft
    || $resourceProjectionChanged->eventName !== ResourceProjectionChanged::EVENT_NAME
    || $resourceProjectionChanged->payload['resource_ref'] !== [
        'app_key' => 'sample',
        'resource_key' => 'sample.expense_report',
        'resource_id' => 'report-1',
    ]
    || ! $resourceProjectionChanged->legalEntityScope?->isExplicit()
    || $resourceProjectionChanged->actor?->actorId !== null
    || $receiptPublisher->publishWithReceipt($draft)->publicationId !== 'publication-1'
    || $currentStateReconcilers->reconcile('sample.expense_report.submitted') !== 1
    || ! LegalEntityScope::tenantWide()->isTenantWide()
    || ! LegalEntityScope::explicit(7)->isExplicit()
    || ! LegalEntityScope::inherit()->inherits()
) {
    throw new RuntimeException('Event draft publication contract changed unexpectedly.');
}

$outbox = new class implements Outbox
{
    public function write(
        string $eventName,
        string $aggregateType,
        string $aggregateId,
        array $payload,
        ?string $correlationId = null,
        ?string $causationId = null,
        array $headers = [],
    ): object {
        return new stdClass;
    }

    public function writeEnvelope(EventEnvelope $envelope): object
    {
        return new stdClass;
    }
};

$consumer = new class implements InboxConsumer
{
    public function run(EventEnvelope $envelope, string $consumerKey, callable $handler): ConsumerResult
    {
        $handler($envelope);

        return ConsumerResult::processed();
    }
};

$listeners = new class implements AppEventListeners
{
    /** @var list<array<string, mixed>> */
    public array $registrations = [];

    public function listen(
        string $ownerAppKey,
        string|array $events,
        callable|string|array $listener,
        ?EventConsumerRegistration $consumer = null,
        ?callable $reconciler = null,
    ): void {
        $this->registrations[] = [
            'owner' => $ownerAppKey,
            'events' => $events,
            'listener' => $listener,
            'consumer' => $consumer,
            'reconciler' => $reconciler,
        ];
    }
};

$consumerRegistration = new EventConsumerRegistration(
    consumerKey: 'sample.approval-result',
    recoveryMode: EventRecoveryMode::Replay,
);
$reconcileRegistration = new EventConsumerRegistration(
    consumerKey: 'sample.current-state-projection',
    recoveryMode: EventRecoveryMode::Reconcile,
);
$allPublicRegistration = new EventConsumerRegistration(
    consumerKey: 'sample.public-events',
    recoveryMode: EventRecoveryMode::Ephemeral,
    supportedSchemaVersion: 2,
    subscriptionMode: EventSubscriptionMode::AllPublicEvents,
);
$reconciler = static function (): void {};

$listeners->listen(
    'sample',
    ['outbox:approval.case.completed'],
    static function (): void {},
    $reconcileRegistration,
    $reconciler,
);
$listeners->listen(
    'sample',
    [],
    static function (): void {},
    $allPublicRegistration,
);

$authorizer = new class implements EventActorAuthorizer
{
    public function allows(Actor $actor, EventEnvelope $envelope): bool
    {
        return true;
    }
};

$delegatedListeners = new class implements ActorDelegatedAppEventListeners
{
    /** @var list<array<string, mixed>> */
    public array $registrations = [];

    public function listenDelegated(
        string $ownerAppKey,
        string|array $events,
        callable|string|array $listener,
        string $authorizer,
        ?EventConsumerRegistration $consumer = null,
        ?callable $reconciler = null,
    ): void {
        $this->registrations[] = [
            'owner' => $ownerAppKey,
            'events' => $events,
            'listener' => $listener,
            'authorizer' => $authorizer,
            'consumer' => $consumer,
            'reconciler' => $reconciler,
        ];
    }
};

$delegatedListeners->listenDelegated(
    'sample',
    'outbox:approval.case.completed',
    static function (): void {},
    $authorizer::class,
    $consumerRegistration,
);

$handled = false;
$result = $consumer->run($envelope, 'sample.test', function () use (&$handled): void {
    $handled = true;
});

if (! $outbox->writeEnvelope($envelope) instanceof stdClass
    || ! $handled
    || ! $result->wasProcessed()
    || ! $result->handlerExecuted()
    || ConsumerResult::alreadyProcessed()->handlerExecuted()
    || $listeners->registrations[0]['owner'] !== 'sample'
    || $listeners->registrations[0]['events'] !== ['outbox:approval.case.completed']
    || $listeners->registrations[0]['consumer'] !== $reconcileRegistration
    || $listeners->registrations[0]['reconciler'] !== $reconciler
    || $consumerRegistration->consumerKey !== 'sample.approval-result'
    || $consumerRegistration->recoveryMode !== EventRecoveryMode::Replay
    || $consumerRegistration->supportedSchemaVersion !== 1
    || $consumerRegistration->subscriptionMode !== EventSubscriptionMode::Exact
    || $listeners->registrations[1]['events'] !== []
    || $listeners->registrations[1]['consumer'] !== $allPublicRegistration
    || $allPublicRegistration->supportedSchemaVersion !== 2
    || $allPublicRegistration->subscriptionMode !== EventSubscriptionMode::AllPublicEvents
    || array_map(static fn (EventRecoveryMode $mode): string => $mode->value, EventRecoveryMode::cases())
        !== ['replay', 'reconcile', 'ephemeral']
    || array_map(static fn (EventSubscriptionMode $mode): string => $mode->value, EventSubscriptionMode::cases())
        !== ['exact', 'all_public_events']
    || $delegatedListeners->registrations[0]['owner'] !== 'sample'
    || $delegatedListeners->registrations[0]['authorizer'] !== $authorizer::class
    || $delegatedListeners->registrations[0]['consumer'] !== $consumerRegistration
    || HandoffResultState::values(
        HandoffResultState::Accepted,
        HandoffResultState::RetryableFailed,
    ) !== ['ACCEPTED', 'RETRYABLE_FAILED']
    || ! HandoffResultState::RetryableFailed->isRetryableFailure()
    || HandoffResultState::TerminalFailed->isRetryableFailure()
) {
    throw new RuntimeException('Event runtime contracts changed unexpectedly.');
}

try {
    new EventConsumerRegistration('approval-result', EventRecoveryMode::Replay);
    throw new RuntimeException('Event consumer keys must be canonical and App-owned.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

try {
    new EventConsumerRegistration('sample.invalid-version', EventRecoveryMode::Replay, 0);
    throw new RuntimeException('Event consumer supported schema versions must be positive.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

$identityUuid = $envelope->identityUuid();

if ($identityUuid !== $envelope->identityUuid()
    || preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/', $identityUuid) !== 1
) {
    throw new RuntimeException('Envelope identityUuid contract changed unexpectedly.');
}

fwrite(STDOUT, "Event contracts passed.\n");
