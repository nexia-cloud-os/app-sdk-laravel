<?php

declare(strict_types=1);

namespace Nexia\Approval;

/** An App-owned policy condition, identified independently of editable rule rows. */
final readonly class ApprovalRequirementContext
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $defaultRequired,
    ) {
        if (trim($key) === '' || strlen($key) > 255 || trim($label) === '') {
            throw new \InvalidArgumentException('Approval requirement contexts need a stable key and a human label.');
        }
    }
}
