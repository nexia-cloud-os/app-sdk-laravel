<?php

declare(strict_types=1);

namespace Nexia\Process;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Frozen App boundary payload for one authorized Process UserTask domain submission. */
final readonly class ProcessUserTaskSubmissionInvocation
{
    /** @param array<string, mixed> $input */
    public function __construct(
        public string $appKey,
        public string $actionKey,
        public string $formKey,
        public string $formVersion,
        public string $processInstancePublicId,
        public int|string $userTaskKey,
        public string $elementId,
        public string $idempotencyKey,
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $resourceRef,
        public array $input,
    ) {}
}
