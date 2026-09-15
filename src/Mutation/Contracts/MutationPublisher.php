<?php

declare(strict_types=1);

namespace Nexia\Mutation\Contracts;

use Nexia\Mutation\MutationOperation;

/**
 * Host capability for mutations that Eloquent Resource Catalog observers
 * cannot see, such as bulk query updates and non-resource semantic actions.
 *
 * The host derives tenant, actor, initiator, request, surface, and correlation
 * context from its trusted runtime. App callers supply none of it. Publication
 * occurs only after the current transaction commits, or immediately when no
 * transaction is active.
 */
interface MutationPublisher
{
    /**
     * Record a Resource Catalog change after its surrounding write succeeds.
     * `Succeeded` is an action-only operation and is invalid here.
     */
    public function resourceChanged(
        string $resourceKey,
        MutationOperation $operation,
        ?string $resourceId = null,
    ): void;

    /** Record a semantic action only after it has succeeded. */
    public function actionSucceeded(string $actionKey): void;
}
