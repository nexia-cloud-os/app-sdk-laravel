<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Nexia\Attachments\ReadableFile;
use Nexia\Identity\Contracts\Actor;

/**
 * Claims one clean Resource Import upload and exposes its durable, authorized
 * bytes without leaking the host UploadIntent or Media models to an App.
 */
interface AuthorizedImportFileReader
{
    public function read(
        string $uploadIntentPublicId,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
    ): ?ReadableFile;
}
