<?php

declare(strict_types=1);

namespace Nexia\Permission;

enum AssignmentScope: string
{
    case Tenant = 'tenant';
    case LegalEntity = 'legal_entity';
    case OperatingUnit = 'operating_unit';

    public function supportsGrantAt(self $grantScope): bool
    {
        return $grantScope->rank() <= $this->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Tenant => 0,
            self::LegalEntity => 1,
            self::OperatingUnit => 2,
        };
    }
}
