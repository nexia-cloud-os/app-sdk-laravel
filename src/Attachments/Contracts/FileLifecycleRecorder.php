<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Nexia\Attachments\FileLifecycleEvent;

/** Records a bounded, durable fact about a file without granting file access. */
interface FileLifecycleRecorder
{
    public function record(FileLifecycleEvent $event): void;
}
