<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureRequestPasswordVerifier;

/**
 * App-neutral credential derivation boundary. Core owns the password policy
 * and returns only opaque hash-derived material for a durable App handoff.
 */
interface SignatureRequestCredentialHost
{
    public function deriveRequestPasswordVerifier(string $password): SignatureRequestPasswordVerifier;
}
