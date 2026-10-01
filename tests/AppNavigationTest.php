<?php

declare(strict_types=1);

use Nexia\Navigation\AppNavigation;

require dirname(__DIR__).'/vendor/autoload.php';

$screen = ['id' => 'trial-notes', 'app_key' => 'trial', 'route' => '/apps/trial/notes',
    'permission' => 'trial.note.read', 'label_key' => 'trial.note.list.title',
    'group_id' => 'operations', 'subgroup_id' => 'old', 'sort' => 500, 'icon' => 'box'];
$navigation = [['screen' => 'trial-notes', 'group' => 'management', 'sort' => 20, 'icon' => 'book']];
$resolved = AppNavigation::resolve(['navigation' => $navigation], 'trial', [$screen]);
assert($resolved === [[...$screen, 'group_id' => 'management', 'subgroup_id' => null, 'sort' => 20, 'icon' => 'book']]);
assert(AppNavigation::resolve(['navigation' => []], 'trial', [$screen]) === []);
assert(AppNavigation::resolve([], 'trial', [$screen]) === [$screen]);
foreach ([
    null, ['screen' => 'trial-notes'],
    [...$navigation, ...$navigation],
    [['screen' => 'foreign-notes']],
    [['screen' => 'trial-notes', 'permission' => null]],
    [['screen' => 'trial-notes', 'route' => '/api/private']],
    [['screen' => 'trial-notes', 'group' => 'unknown']],
    [['screen' => 'trial-notes', 'sort' => -1]],
    [['screen' => 'trial-notes', 'subgroup' => 'orphan']],
    [['screen' => 'trial-notes', 'icon' => '../invalid']],
    [['screen' => 'trial-missing']],
] as $invalid) {
    try {
        AppNavigation::resolve(['navigation' => $invalid], 'trial', [$screen]);
        throw new LogicException('Invalid menu was accepted.');
    } catch (RuntimeException) {
        // Expected declaration or screen-resolution rejection.
    }
}
echo "App menu selection preserves screen authority and rejects invalid declarations.\n";
