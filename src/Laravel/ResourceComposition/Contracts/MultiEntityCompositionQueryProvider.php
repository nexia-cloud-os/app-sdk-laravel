<?php

declare(strict_types=1);

namespace Nexia\Laravel\ResourceComposition\Contracts;

/**
 * A composition provider that applies every authorized organization target
 * before joins, aggregation and pagination, without duplicating its grain.
 */
interface MultiEntityCompositionQueryProvider extends AuthorizedCompositionQueryProvider {}
