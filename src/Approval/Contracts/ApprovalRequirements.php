<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

/** Tenant operation requirements. Approval-line selection is a separate concern. */
interface ApprovalRequirements
{
    /**
     * Resolve a company override, preserving the App's existing conditional rule
     * when no override exists. Capture this result with submission evidence;
     * never reevaluate an already submitted request during outcome handling.
     */
    public function required(string $bindingKey, int|string|null $legalEntityKey, bool $default, ?string $contextKey = null): bool;
}
