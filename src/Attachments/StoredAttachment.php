<?php

declare(strict_types=1);

namespace Nexia\Attachments;

/** Host attachment identity returned without exposing the host Eloquent model. */
final readonly class StoredAttachment
{
    public function __construct(public string $publicId) {}
}
