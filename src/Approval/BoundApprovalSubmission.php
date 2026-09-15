<?php

declare(strict_types=1);

namespace Nexia\Approval;

use Nexia\Approval\Domain\ApprovalLineDefinition;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

final readonly class BoundApprovalSubmission
{
    /**
     * @param array<string, mixed> $documentValues
     * @param list<int> $referenceUserIds
     * @param list<int> $circulationUserIds
     */
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $drafter,
        public string $templateKey,
        public int $templateVersion,
        public string $bindingKey,
        public string $bindingVersion,
        public ResourceRef $resourceRef,
        public string $resourceVersion,
        public array $documentValues,
        public ApprovalLineDefinition $lineDefinition,
        public string $title,
        public ?string $supplementalBody = null,
        public array $referenceUserIds = [],
        public array $circulationUserIds = [],
        public ?string $summary = null,
        public ?string $typeLabel = null,
        public ?string $category = null,
        public ?string $idempotencyKey = null,
        public ?string $idempotencyFingerprint = null,
    ) {}
}
