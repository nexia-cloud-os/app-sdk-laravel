<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use RuntimeException;

/** Only fixed diagnostic codes may cross the isolated declaration boundary. */
final class CatalogValidationException extends RuntimeException
{
    public function __construct(string $code)
    {
        parent::__construct(in_array($code, ['catalog_route_missing', 'catalog_handler_invalid', 'catalog_contract_unsupported', 'catalog_reference_invalid'], true)
            ? $code : 'catalog_invalid');
    }
}
