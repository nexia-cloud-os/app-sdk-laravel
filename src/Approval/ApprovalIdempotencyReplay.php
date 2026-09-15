<?php

declare(strict_types=1);

namespace Nexia\Approval;

final readonly class ApprovalIdempotencyReplay
{
    public function __construct(
        public int $resourceId,
        public int $statusCode = 200,
        public bool $replayed = false,
    ) {}
}
