<?php

declare(strict_types=1);

namespace Nexia\Organization\Contracts;

use Nexia\Identity\Contracts\Actor;

/** Explicit organization conditions for a host-governed App write, not a shared lock. */
interface OrganizationWritePreconditions
{
    /**
     * Register before App persistence using the current actor and declared organization authority.
     * Use canonical lowercase public IDs and explicit booleans/date; predicates must be nonempty.
     * Identical registration is repeatable; changing predicates for the same target/date is rejected.
     * The combined Party/organization target limit is 100 per request.
     *
     * Core rechecks selected predicates under locks at completion. A conflict needs review and
     * may retain committed App data. Returning confirms registration, not final save success.
     * Unsupported contexts reject; this does not replace OrganizationDirectory lock methods.
     *
     * @param array{type: 'legal_entity', id: string, expected: array{active: bool}}|array{type: 'operating_unit', id: string, legal_entity_id: string, as_of: string, expected: array{affiliated?: bool, effective?: bool}} $condition
     */
    public function register(Actor $actor, array $condition): void;
}
