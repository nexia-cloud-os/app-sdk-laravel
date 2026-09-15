<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Host-owned person Party provisioning for App-owned directory subjects. */
interface PersonPartyProvisioner
{
    /** Reuse the unique active email match, or create a person Party. */
    public function ensureForDirectorySubject(string $displayName, ?string $email): Party;
}
