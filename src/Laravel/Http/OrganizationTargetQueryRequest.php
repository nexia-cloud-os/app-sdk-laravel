<?php

declare(strict_types=1);

namespace Nexia\Laravel\Http;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Nexia\Organization\OrganizationTargetQuery;
use Nexia\Organization\OrganizationTargetQueryException;

/** Laravel transport adapter for canonical `*_public_ids[]` organization filters. */
final class OrganizationTargetQueryRequest
{
    public static function from(Request $request): OrganizationTargetQuery
    {
        try {
            return OrganizationTargetQuery::fromInput($request->query());
        } catch (OrganizationTargetQueryException $exception) {
            throw ValidationException::withMessages([$exception->field => [$exception->getMessage()]]);
        }
    }
}
