<?php

declare(strict_types=1);

namespace Nexia\ResourceReference\Contracts;

use Nexia\Permission\SubjectPopulation;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** Owner guarantees that widget search results are limited to the stated population. */
interface ResourceReferenceWidgetPopulationContribution extends ResourceReferenceResolutionContribution
{
    public function authorizesWidgetPopulation(ResourceReferenceResolutionContext $context, SubjectPopulation $population): bool;
}
