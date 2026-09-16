<?php

declare(strict_types=1);

use Nexia\Events\EventEnvelope;
use Nexia\Notification\Contracts\NotificationContribution;
use Nexia\Notification\Contracts\NotificationIntent;
use Nexia\Notification\Contracts\NotificationPublisher;
use Nexia\Notification\NotificationCategory;
use Nexia\Notification\NotificationPublication;
use Nexia\ResourceReference\ResourceRef;

require dirname(__DIR__).'/vendor/autoload.php';

$intent = new class implements NotificationIntent
{
    public function key(): string
    {
        return 'workshop.note.completed';
    }

    public function category(): string
    {
        return 'workshop.work';
    }

    public function policySnapshot(): array
    {
        return ['preference_mode' => 'default_on', 'realtime_attention' => 'center_only', 'realtime_variant' => 'info'];
    }

    public function deepLink(array $params): ?string
    {
        return '/apps/workshop/notes/'.$params['note_id'];
    }

    public function render(array $params, string $locale): array
    {
        return ['title' => $locale === 'ko' ? '노트 완료' : 'Note complete', 'body' => null];
    }
};
$contribution = new class($intent) implements NotificationContribution
{
    public function __construct(private NotificationIntent $intent) {}

    public function notificationCategories(): iterable
    {
        return [new NotificationCategory('workshop.work', 'workshop.notifications.work', 60)];
    }

    public function notificationIntents(): iterable
    {
        return [$this->intent];
    }
};
$publication = new NotificationPublication(
    intentKey: $intent->key(),
    owner: new ResourceRef('workshop', 'workshop.note', 'N-1', 'Note N-1'),
    legalEntityId: 1,
    recipientIds: [7],
    params: ['note_id' => 'N-1'],
    dedupeKey: 'note:N-1:completed',
);
$publisher = new class implements NotificationPublisher
{
    public ?NotificationPublication $publication = null;

    public function publish(EventEnvelope $source, NotificationPublication $publication): void
    {
        $this->publication = $publication;
    }
};
$publisher->publish(new EventEnvelope('workshop.note.completed', 'tenant-1', [], 'workshop'), $publication);

if (iterator_count($contribution->notificationIntents()) !== 1
    || $publisher->publication?->intentKey !== 'workshop.note.completed') {
    throw new RuntimeException('App notification contract changed unexpectedly.');
}
