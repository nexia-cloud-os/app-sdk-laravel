<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

/** Publishes additional domain abilities required to observe protected media. */
interface DefinesMediaViewAbilities
{
    /** @return list<string> */
    public function mediaViewAbilities(): array;
}
