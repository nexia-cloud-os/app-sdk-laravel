<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

/** Optional, request-local capability of the approval submission host. */
interface ApprovalSubmissionAvailability
{
    /**
     * The current request can use enqueueBound for this declared finalizer
     * and record its intent in the App transaction without a Core callback.
     */
    public function deferredFor(string $finalizeWorkKey): bool;
}
