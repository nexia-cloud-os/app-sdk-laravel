<?php

declare(strict_types=1);

namespace Nexia\Process;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Frozen App boundary payload for one claimed Process work action. */
final readonly class ProcessWorkActionInvocation
{
    /**
     * @param array<string, mixed> $input
     * @param ?string $elementId Actual BPMN element; null for older callers.
     */
    public function __construct(
        public string $appKey,
        public string $actionKey,
        public string $descriptorVersion,
        public string $processInstancePublicId,
        public int|string $externalTaskKey,
        public string $idempotencyKey,
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $resourceRef,
        public array $input,
        public ?string $elementId = null,
        /** Original case identity for message correlation; never replace with a downstream target. */
        public ?ResourceRef $originResourceRef = null,
        /** @var array<string, ResourceRef|list<ResourceRef>|null> */
        public array $resourceInputs = [],
        public ?\Nexia\Approval\Domain\ApprovalLineDefinition $approvalLine = null,
    ) {}
}
