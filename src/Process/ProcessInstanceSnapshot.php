<?php

declare(strict_types=1);

namespace Nexia\Process;

use Nexia\Process\Domain\Enums\ProcessInstanceStatus;
use Nexia\ResourceReference\ResourceRef;

/** Read-only public projection of a host-owned Process instance. */
final readonly class ProcessInstanceSnapshot
{
    public function __construct(
        public string $publicId,
        public int|string $legalEntityKey,
        public ProcessInstanceStatus $status,
        public ?string $businessKey,
        public ?ResourceRef $resourceRef,
    ) {}
}
