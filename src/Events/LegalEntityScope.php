<?php

declare(strict_types=1);

namespace Nexia\Events;

use InvalidArgumentException;

/**
 * Legal Entity intent supplied by an App. Core resolves Inherit against the
 * active authorized execution context at publish time.
 */
final readonly class LegalEntityScope
{
    private const INHERIT = 'inherit';

    private const TENANT_WIDE = 'tenant_wide';

    private const EXPLICIT = 'explicit';

    private function __construct(
        public string $kind,
        public ?int $legalEntityId = null,
    ) {
        if (! in_array($kind, [self::INHERIT, self::TENANT_WIDE, self::EXPLICIT], true)) {
            throw new InvalidArgumentException("Unsupported Legal Entity scope [{$kind}].");
        }

        if ($kind === self::EXPLICIT && ($legalEntityId === null || $legalEntityId <= 0)) {
            throw new InvalidArgumentException('An explicit Legal Entity scope requires a positive identifier.');
        }

        if ($kind !== self::EXPLICIT && $legalEntityId !== null) {
            throw new InvalidArgumentException('Only an explicit Legal Entity scope may carry an identifier.');
        }
    }

    public static function inherit(): self
    {
        return new self(self::INHERIT);
    }

    public static function tenantWide(): self
    {
        return new self(self::TENANT_WIDE);
    }

    public static function explicit(int $legalEntityId): self
    {
        return new self(self::EXPLICIT, $legalEntityId);
    }

    public function inherits(): bool
    {
        return $this->kind === self::INHERIT;
    }

    public function isTenantWide(): bool
    {
        return $this->kind === self::TENANT_WIDE;
    }

    public function isExplicit(): bool
    {
        return $this->kind === self::EXPLICIT;
    }
}
