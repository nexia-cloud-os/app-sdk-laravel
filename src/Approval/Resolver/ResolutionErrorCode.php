<?php

declare(strict_types=1);

namespace Nexia\Approval\Resolver;

enum ResolutionErrorCode: string
{
    case Unresolved = 'unresolved';
    case Inactive = 'inactive';
    case Unauthorized = 'unauthorized';
    case ResolverUnavailable = 'resolver_unavailable';
    case LegalEntityScopeMismatch = 'legal_entity_scope_mismatch';

    public function category(): string
    {
        return match ($this) {
            self::Unresolved => 'resolution',
            self::Inactive, self::Unauthorized => 'authority',
            self::ResolverUnavailable, self::LegalEntityScopeMismatch => 'availability',
        };
    }
}
