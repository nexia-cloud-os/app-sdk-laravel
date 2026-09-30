<?php

declare(strict_types=1);

use Nexia\Attachments\Contracts\DefinesMediaUploadAbilities;
use Nexia\Attachments\Contracts\DefinesMediaViewAbilities;
use Nexia\Laravel\Attachments\Contracts\RequiresMediaOwnerViewAuthorization;
use Spatie\MediaLibrary\HasMedia;

require dirname(__DIR__).'/vendor/autoload.php';

if (! interface_exists(RequiresMediaOwnerViewAuthorization::class)
    || is_subclass_of(RequiresMediaOwnerViewAuthorization::class, HasMedia::class)
    || ! method_exists(DefinesMediaUploadAbilities::class, 'mediaUploadAbilities')
    || ! method_exists(DefinesMediaViewAbilities::class, 'mediaViewAbilities')
) {
    throw new RuntimeException('Media owner contracts changed unexpectedly.');
}

fwrite(STDOUT, "Media owner contracts passed.\n");
