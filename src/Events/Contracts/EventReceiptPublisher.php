<?php

declare(strict_types=1);

namespace Nexia\Events\Contracts;

use Nexia\Events\EventDraft;
use Nexia\Events\EventPublicationReceipt;

/** Optional capability for Apps that must durably link state to a published event. */
interface EventReceiptPublisher extends EventPublisher
{
    public function publishWithReceipt(EventDraft $draft): EventPublicationReceipt;
}
