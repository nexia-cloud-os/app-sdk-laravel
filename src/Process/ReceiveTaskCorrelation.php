<?php

declare(strict_types=1);

namespace Nexia\Process;

use InvalidArgumentException;
use Nexia\Process\Domain\Enums\ProcessInstanceStatus;
use Nexia\ResourceReference\ResourceRef;

/** Exact routing evidence required to correlate one host Process receive task. */
final readonly class ReceiveTaskCorrelation
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public int|string $legalEntityKey,
        public string $processInstancePublicId,
        public ?string $businessKey,
        public ResourceRef $resourceRef,
        public string $elementId,
        public string $topic,
        public array $payload,
        public ?ProcessInstanceStatus $requiredInstanceStatus = null,
    ) {
        if ($processInstancePublicId === '' || $processInstancePublicId !== trim($processInstancePublicId)
            || $elementId === '' || $elementId !== trim($elementId)
            || $topic === '' || $topic !== trim($topic)
            || ($businessKey !== null && ($businessKey === '' || $businessKey !== trim($businessKey)))) {
            throw new InvalidArgumentException('Receive-task correlation identifiers must be non-blank and normalized.');
        }
    }
}
