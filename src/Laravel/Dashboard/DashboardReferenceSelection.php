<?php

declare(strict_types=1);

namespace Nexia\Laravel\Dashboard;

use Illuminate\Http\Request;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

/** The runtime restores this narrowly signed population before invoking an App provider. */
final class DashboardReferenceSelection
{
    public static function isDirectReportPage(Request $request, ResourceReferenceResolutionContext $context, string $resource, string $purpose): bool
    {
        $scope = $request->attributes->get('nexia.runtime.reference_scope');
        $selection = is_array($scope) ? ($scope['widget_population_reference'] ?? null) : null;

        return is_array($selection) && ($selection['dashboard_page'] ?? null) === true
            && ($selection['population'] ?? null) === 'direct_reports'
            && ($selection['resource_key'] ?? null) === $resource && ($selection['purpose'] ?? null) === $purpose
            && ($selection['actor_public_id'] ?? null) === $context->actor->publicId()
            && ($selection['legal_entity_public_id'] ?? null) === $context->legalEntity?->publicId()
            && $context->purpose === $purpose && $context->operatingUnit === null
            && $context->filters === [] && $context->sort === null && ! $context->includeProtectedValues
            && $context->effectiveThrough === null && $request->json('method') === 'searchPage';
    }
}
