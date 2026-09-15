<?php

declare(strict_types=1);

namespace Nexia\Organization;

use InvalidArgumentException;

final class OrganizationTargetQueryException extends InvalidArgumentException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
