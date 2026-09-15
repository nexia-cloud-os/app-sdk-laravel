<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

/** Owner-App contribution for resolving one resource identity without exposing its model. */
interface ResourceReferenceResolutionContribution
{
    public function resourceKey(): string;

    public function resolve(
        string $resourceId,
        ResourceReferenceResolutionContext $context,
    ): ?ResolvedResourceReference;
}
