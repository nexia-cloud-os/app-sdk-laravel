<?php

declare(strict_types=1);

namespace Nexia\Notification\Contracts;

use Nexia\Notification\NotificationCategory;

/** App-owned notification taxonomy discovered through the App contribution location. */
interface NotificationContribution
{
    /** @return iterable<NotificationCategory> */
    public function notificationCategories(): iterable;

    /** @return iterable<NotificationIntent> */
    public function notificationIntents(): iterable;
}
