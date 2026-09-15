<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Nexia\Attachments\ReadableFile;
use Nexia\Identity\Contracts\Actor;

/**
 * Host-authorized access to one tenant file. Missing, cross-boundary, and
 * unauthorized files all resolve to null so Apps fail closed.
 */
interface AuthorizedFileReader
{
    public function read(
        string $filePublicId,
        int|string $legalEntityKey,
        Actor $actor,
    ): ?ReadableFile;
}
