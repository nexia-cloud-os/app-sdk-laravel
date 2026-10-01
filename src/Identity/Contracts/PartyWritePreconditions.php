<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** Explicit reference conditions for a host-governed App write, not a shared database lock. */
interface PartyWritePreconditions
{
    /**
     * Register before saving App data. Requires the current actor and directory.party.read.
     * At most 100 distinct canonical lowercase Party IDs per write. Identical registration
     * is repeatable; changing an existing target's conditions is rejected.
     *
     * Only selected predicates are checked now and under Core locks at final completion.
     * A later mismatch leaves the operation needing review; App data may already exist.
     * Returning does not finalize the operation or hold Core locks for the App transaction.
     * Unsupported execution contexts must reject, never fall back to an ordinary lookup.
     *
     * @param array{person?: bool, organization?: bool, archived?: bool} $expected Nonempty explicit predicates.
     */
    public function register(Actor $actor, string $publicId, array $expected): void;
}
