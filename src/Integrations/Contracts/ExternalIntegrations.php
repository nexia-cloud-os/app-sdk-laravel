<?php

declare(strict_types=1);

namespace Nexia\Integrations\Contracts;

use Nexia\Identity\Contracts\Actor;

/** Execute explicitly shared operations without exposing connection credentials to an App. */
interface ExternalIntegrations
{
    /**
     * Submit after the App transaction commits, preferably from durable App work.
     * Reusing the key with identical input returns the same execution; changed input is rejected.
     * The returned public ID identifies a queued execution, not a successful remote effect.
     *
     * @param array<string, mixed> $input
     */
    public function submit(Actor $actor, string $operationId, array $input, string $idempotencyKey): string;

    /**
     * Only the originating App and actor may read the result, subject to current connection authority.
     * Retrying is a pending webhook delivery; keep polling the same execution ID.
     * Unknown means the remote effect may have happened; do not automatically submit it again.
     *
     * @return array{public_id:string,status:string,error_code:?string,result:?array}
     */
    public function result(Actor $actor, string $executionId): array;
}
