<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Nexia\Attachments\AttachmentSummary;
use Nexia\Attachments\AttachmentTarget;
use Nexia\Identity\Contracts\Actor;

interface AttachmentDirectory
{
    /** @param list<AttachmentTarget> $targets @return list<AttachmentSummary> */
    public function listForTargets(array $targets, Actor $actor, int $limit = 250): array;
}
