<?php

declare(strict_types=1);

namespace Nexia\Laravel\Attachments\Contracts;

use Spatie\MediaLibrary\HasMedia;

/** Requires each media read to authorize through its owning resource policy. */
interface RequiresMediaOwnerViewAuthorization extends HasMedia {}
