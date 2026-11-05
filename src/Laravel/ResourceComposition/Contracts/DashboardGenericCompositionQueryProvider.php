<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/**
 * Allows a dashboard Resource query to intersect records with the provider's
 * complete, unpaginated and identity-unique selected-target scope. The host
 * separately applies the Resource's record policy before paging or aggregation.
 */
interface DashboardGenericCompositionQueryProvider extends MultiEntityCompositionQueryProvider {}
