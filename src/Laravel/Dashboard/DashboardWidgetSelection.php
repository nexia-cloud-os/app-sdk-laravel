<?php

declare(strict_types=1);

namespace Nexia\Laravel\Dashboard;

use Illuminate\Http\Request;

final class DashboardWidgetSelection
{
    /** Private request-lifetime memoization, cleared by the dashboard runtime after execution. */
    public static function requestCache(Request $request): array
    {
        $cache = $request->attributes->get('nexia.dashboard.request_cache');

        return is_array($cache) ? $cache : [];
    }

    public static function setRequestCache(Request $request, array $cache): void
    {
        $request->attributes->set('nexia.dashboard.request_cache', $cache);
    }

    /** @return list<string>|null Null when the request is not a scoped dashboard widget. */
    public static function legalEntityPublicIds(Request $request): ?array
    {
        $ids = $request->attributes->get('nexia.dashboard.legal_entity_public_ids');

        return is_array($ids) && array_is_list($ids) ? $ids : null;
    }

    /** @return list<int>|null Internal keys correspond to the authenticated selection. */
    public static function legalEntityKeys(Request $request): ?array
    {
        $keys = $request->attributes->get('nexia.dashboard.legal_entity_keys');

        return is_array($keys) && array_is_list($keys) ? $keys : null;
    }

    /** Null outside a scoped widget or when this source did not declare the group. */
    public static function visibility(Request $request, int $legalEntityKey, string $group): ?bool
    {
        $visibility = $request->attributes->get('nexia.dashboard.visibility');
        $value = is_array($visibility) ? ($visibility[$legalEntityKey][$group] ?? null) : null;

        return is_bool($value) ? $value : null;
    }
}
