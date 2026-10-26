<?php

declare(strict_types=1);

namespace Nexia\Identity\Contracts;

/** End one person's company participation and scoped authority without deleting their identity. */
interface PersonLegalEntityAccess
{
    /**
     * An idempotent operation. The caller retains the source workflow and retries failures.
     * Returns completed/unlinked, or review_required when tenant-wide authority cannot be split safely.
     * @return array{status:string,user_public_id:?string,revoked_grants:int}
     */
    public function end(Actor $actor, string $personPartyPublicId, int|string $legalEntityKey, string $sourceReference): array;
}
