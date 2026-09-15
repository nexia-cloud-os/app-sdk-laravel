<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

/** Publishes the domain abilities Core must authorize before media upload. */
interface DefinesMediaUploadAbilities
{
    /** @return non-empty-list<string> */
    public function mediaUploadAbilities(): array;
}
