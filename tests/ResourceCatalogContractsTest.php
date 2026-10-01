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

$custom = ['/:id' => ['component' => 'CustomDetail', 'mode' => 'show'],
    '/:id/edit' => ['component' => 'CustomEditor', 'mode' => 'edit', 'permissionAction' => 'update_identity'],
    '/workflow' => 'CustomWorkflow'];
assert((new ShellResourceDescriptor(overrides: $custom))->overrides === $custom);
assert(ShellResourceDescriptor::fromArray(['overrides' => $custom])->overrides === $custom);
foreach ([null, 42, '', '../code.js', [], ['component' => 'Editor'],
    ['component' => 'Editor', 'mode' => 'save'],
    ['component' => 'Editor', 'mode' => 'edit', 'permissionAction' => null],
    ['component' => 'Editor', 'mode' => 'edit', 'permissionAction' => ''],
    ['component' => 'Editor', 'mode' => 'edit', 'permissionAction' => 'foreign/path'],
    ['component' => 'Editor', 'mode' => 'edit', 'extra' => 'ignored'],
] as $invalid) {
    foreach ([false, true] as $fromArray) {
        try {
            $fromArray ? ShellResourceDescriptor::fromArray(['overrides' => ['/custom' => $invalid]])
                : new ShellResourceDescriptor(overrides: ['/custom' => $invalid]);
            throw new RuntimeException('Invalid shell override was accepted.');
        } catch (InvalidArgumentException) {
        }
    }
}

fwrite(STDOUT, "Resource Catalog contracts passed.\n");

$appWidgets = new class implements \Nexia\Dashboard\Contracts\DashboardWidgetContribution
{
    public static function dashboardWidgets(): array
    {
        return [];
    }
};
assert(! $appWidgets instanceof ResourceCatalogContribution);
assert(! method_exists($appWidgets, 'resourceModelClass'));

assert(ShellResourceDescriptor::fromArray(['shapes' => ['list', 'record']])->shapes === ['list', 'record']);
foreach ([['record', 'show'], ['record', 'form'], ['record', 'record'], ['unknown'], ['named' => 'record'], [42]] as $invalidShapes) {
    $rejected = false;
    try {
        new ShellResourceDescriptor(shapes: $invalidShapes);
    } catch (InvalidArgumentException) {
        $rejected = true;
    }
    assert($rejected, 'Conflicting or invalid record shapes must fail.');
}
