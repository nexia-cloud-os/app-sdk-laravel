<?php

declare(strict_types=1);

namespace Nexia\Approval;

use Nexia\ResourceReference\ResourceRef;

/** Read-only approval case fields safe to return across an App boundary. */
final readonly class ApprovalCaseSummary
{
    public function __construct(
        public int|string $key,
        public string $publicId,
        public string $state,
        public ResourceRef $resourceRef,
        public string $bindingKey,
        public string $bindingVersion,
        public string $bindingAppKey,
        public string $bindingResourceKey,
        public string $bindingActionKey,
        public string $resourceVersion,
    ) {}
}
