<?php

declare(strict_types=1);

namespace Nexia\Permission\Contracts;

/** Candidate self-access permissions an administrator may explicitly grant to Legal Entity members. */
interface LegalEntityMemberSelfAccessContribution
{
    /** @return list<string> */
    public static function legalEntityMemberSelfAccessKeys(): array;
}
