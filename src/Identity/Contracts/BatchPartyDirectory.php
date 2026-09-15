<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

use Nexia\Identity\Contracts\Party;

/** Optional bulk Party lookup capability for import and reconciliation jobs. */
interface BatchPartyDirectory
{
    /**
     * @param  list<string>  $emails
     * @return array<string, list<Party>> normalized email => zero, one, or multiple matches
     */
    public function activePeopleByEmails(array $emails, int $limitPerEmail = 2): array;
}
