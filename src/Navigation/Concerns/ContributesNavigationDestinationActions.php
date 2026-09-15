<?php

declare(strict_types=1);

namespace Nexia\Navigation\Concerns;

/**
 * Exposes an App resource contributor's navigation actions to the shell.
 */
trait ContributesNavigationDestinationActions
{
    /** @return list<array<string, mixed>> */
    public static function agentNavigationActions(): array
    {
        if (! method_exists(static::class, 'agentNavigationActionDefinitions')) {
            return [];
        }

        try {
            $actions = static::agentNavigationActionDefinitions();
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($actions)) {
            return [];
        }

        return array_values(array_filter(
            $actions,
            static fn (mixed $action): bool => is_array($action)
                && is_string($action['action'] ?? null),
        ));
    }
}
