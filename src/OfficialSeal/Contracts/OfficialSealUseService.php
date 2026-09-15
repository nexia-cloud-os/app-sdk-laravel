<?php

declare(strict_types=1);

namespace Nexia\OfficialSeal\Contracts;

use Nexia\Identity\Contracts\Actor;
use Nexia\OfficialSeal\OfficialSealAsset;

interface OfficialSealUseService
{
    /**
     * Acquires an active source image for one server-side rendering operation.
     * Implementations must audit the actor and purpose and must never create a browser URL.
     */
    public function acquire(
        int|string $legalEntityKey,
        ?string $sealPublicId,
        string $purpose,
        Actor $actor,
        ?string $ipAddress = null,
        ?string $idempotencyKey = null,
    ): ?OfficialSealAsset;

    /** Records the checksum of the generated output that consumed the acquisition. */
    public function confirmOutput(
        string $usageId,
        string $outputChecksum,
        Actor $actor,
        ?string $ipAddress = null,
    ): void;
}
