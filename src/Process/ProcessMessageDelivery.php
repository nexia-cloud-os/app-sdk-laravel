<?php

declare(strict_types=1);

namespace Nexia\Process;

use InvalidArgumentException;
use Nexia\Process\Domain\Enums\ProcessInstanceStatus;
use Nexia\ResourceReference\ResourceRef;

/** Durable, idempotent message addressed to one exact Process activity. */
final readonly class ProcessMessageDelivery
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $messageIdentity,
        public int|string $legalEntityKey,
        public string $processInstancePublicId,
        public ?string $businessKey,
        public ResourceRef $resourceRef,
        public string $elementId,
        public string $topic,
        public array $payload,
        public ?string $referenceId = null,
        public ?ProcessInstanceStatus $requiredInstanceStatus = null,
    ) {
        foreach ([
            'processInstancePublicId' => $processInstancePublicId,
            'elementId' => $elementId,
            'topic' => $topic,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Process message {$field} must be non-blank and normalized.");
            }
        }
        ProcessMessageIdentity::assertValid($messageIdentity);
        if ($businessKey !== null && ($businessKey === '' || $businessKey !== trim($businessKey))) {
            throw new InvalidArgumentException('Process message businessKey must be null or a normalized non-blank string.');
        }
        if ($referenceId !== null && ($referenceId === '' || $referenceId !== trim($referenceId))) {
            throw new InvalidArgumentException('Process message referenceId must be null or a normalized non-blank string.');
        }
    }
}
