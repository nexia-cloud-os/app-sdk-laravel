<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Optional owner admission for Core-restored exact-self reads, without directory grants. */
interface ResourceReferenceSelfServiceContribution extends ResourceReferenceResolutionContribution
{
    /**
     * Admit only explicit self-service purposes. Every supported resolution/search
     * method must constrain results to this actor and organization, including when
     * caller-supplied identifiers, filters or dates select another person's rows.
     */
    public function authorizesSelfService(ResourceReferenceResolutionContext $context): bool;
}
