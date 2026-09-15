<?php

declare(strict_types=1);

namespace Nexia\OfficialSeal\Contracts;

use Nexia\OfficialSeal\OfficialSealSummary;

interface OfficialSealDirectory
{
    /** @return list<OfficialSealSummary> */
    public function activeForLegalEntity(int|string $legalEntityKey, ?string $asOf = null): array;
}
