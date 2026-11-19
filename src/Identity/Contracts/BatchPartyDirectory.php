<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\Contracts\Party;

/** Optional bulk Party lookup capability for import and reconciliation jobs. */
interface BatchPartyDirectory
{
    /**
     * For the model API, use `Party::peopleByEmails($emails, $limitPerEmail)`.
     * Returns model collections keyed by normalized email. Requires the batch host contract. The legacy
     * `IdentityQueries` email batch can fall back to single lookups.
     *
     * @param  list<string>  $emails
     * @return array<string, list<Party>> normalized email => zero, one, or multiple matches
     * @see \Nexia\Laravel\Directory\Party
     */
    public function activePeopleByEmails(array $emails, int $limitPerEmail = 2): array;
}
