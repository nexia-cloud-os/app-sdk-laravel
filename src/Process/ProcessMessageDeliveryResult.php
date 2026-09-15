<?php

declare(strict_types=1);

namespace Nexia\Process;

use Nexia\Process\Domain\Enums\ProcessMessageDeliveryStatus;

final readonly class ProcessMessageDeliveryResult
{
    public function __construct(
        public ProcessMessageDeliveryStatus $status,
        public ?string $reason = null,
    ) {}
}
