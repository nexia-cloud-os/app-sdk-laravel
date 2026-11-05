<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

use Nexia\Laravel\ResourceComposition\AuthorizedCompositionQuery;
use Nexia\Laravel\ResourceComposition\CompositionQueryContext;

/**
 * Optional dashboard-only projection; report queries continue to use query().
 * A selectable resource_reference field may declare `dashboard_list_filter`
 * as PUBLIC_REFERENCE_SET_FILTER only when its existing list callback means
 * membership in those projected public IDs, including empty/invalid input.
 * A boolean field may declare `dashboard_encoding` as JSON_BOOLEAN_SCALAR
 * when its SQL projection returns the JSON scalar itself. The runtime preserves
 * boolean, 0/1, string 0/1 and null rather than guessing from text.
 * These declarations do not grant field, row, or organization access.
 */
interface DashboardGenericListProjectionProvider extends DashboardGenericCompositionQueryProvider
{
    /** Declares that a list callback is equivalent to selecting projected public reference IDs. */
    public const PUBLIC_REFERENCE_SET_FILTER = 'public_reference_set';

    /** Projected scalar is equivalent to a single-valued relationship list filter. */
    public const PUBLIC_RELATION_SET_FILTER = 'public_relation_set';

    public const JSON_BOOLEAN_SCALAR = 'json_boolean_scalar';

    public function dashboardGenericListProjection(CompositionQueryContext $context): AuthorizedCompositionQuery;
}
