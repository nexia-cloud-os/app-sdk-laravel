<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Nexia\AppRuntime\ShellResourceDescriptor;
use Nexia\AppRuntime\Concerns\HasShellResource;
use Nexia\AppRuntime\Contracts\ShellResourceContribution;
use Nexia\Contribution\Contracts\ResourceCatalogContribution;

require dirname(__DIR__).'/vendor/autoload.php';

$catalog = new class implements ShellResourceContribution
{
    use HasShellResource;

    public static function resourceKey(): string
    {
        return 'sample.item';
    }

    public static function resourceModelClass(): string
    {
        return Model::class;
    }
};

if (! $catalog instanceof ResourceCatalogContribution
    || $catalog::resourceKey() !== 'sample.item'
    || $catalog::resourceModelClass() !== Model::class
    || ! $catalog::shellResource() instanceof ShellResourceDescriptor
    || $catalog::shellResource()->path !== null
) {
    throw new RuntimeException('Resource Catalog contracts changed unexpectedly.');
}

fwrite(STDOUT, "Resource Catalog contracts passed.\n");
