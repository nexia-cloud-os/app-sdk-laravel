<?php

declare(strict_types=1);

use Nexia\Palette\PaletteCommand;
use Nexia\Palette\Contracts\PaletteCommandContribution;

require dirname(__DIR__).'/vendor/autoload.php';

$command = PaletteCommand::make(
    id: 'sample.open',
    labelKey: 'sample-commands.sample.open.label',
    action: ['type' => 'navigate', 'target' => '/apps/sample'],
    descriptionKey: 'sample-commands.sample.open.description',
    permission: 'sample.read',
    shortcutKeys: ['G', 'S'],
    icon: 'box',
);

if ($command->toArray() !== [
    'id' => 'sample.open',
    'label_key' => 'sample-commands.sample.open.label',
    'description_key' => 'sample-commands.sample.open.description',
    'permission' => 'sample.read',
    'action' => ['type' => 'navigate', 'target' => '/apps/sample'],
    'shortcut_keys' => ['G', 'S'],
    'icon' => 'box',
]) {
    throw new RuntimeException('Palette command shape changed unexpectedly.');
}

$minimalCommand = PaletteCommand::make('sample.minimal', 'sample-commands.sample.minimal.label', ['type' => 'noop']);
if (array_keys($minimalCommand->toArray()) !== ['id', 'label_key', 'permission', 'action']) {
    throw new RuntimeException('Optional palette command fields must be omitted.');
}

$commandContributor = new class implements PaletteCommandContribution
{
    public static function paletteCommandItems(): array
    {
        return [];
    }
};

if (! $commandContributor instanceof PaletteCommandContribution
) {
    throw new RuntimeException('Palette search contracts changed unexpectedly.');
}

fwrite(STDOUT, "Palette contracts passed.\n");
