<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use RuntimeException;

/** Carries a sanitized standard-query failure between host implementations. */
final class ReferenceQueryFailure extends RuntimeException
{
    public function __construct(public readonly string $queryCode, public readonly ?int $httpStatus)
    {
        parent::__construct('Reference query failed.');
    }
}
