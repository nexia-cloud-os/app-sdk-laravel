<?php

declare(strict_types=1);

namespace Nexia\Navigation;

/** Builds record-locate action metadata for agent navigation. */
final class ShellNavigationLocateAction
{
    private const I18N_PREFIX = 'agent-navigation';

    /**
     * @param  string|list<string>|null  $permission
     * @return array<string, mixed>
     */
    public static function make(
        string $action,
        string $url,
        string|array|null $permission,
        ?string $entryKey = null,
    ): array {
        $entryId = $entryKey ?? 'locate';

        return [
            'action' => $action,
            'intent' => 'locate',
            'url' => $url,
            'effect' => 'read_only',
            'requires_user_submit' => false,
            'permission' => $permission,
            'label_key' => implode('.', [self::I18N_PREFIX, $entryId, 'label']),
            'description_key' => implode('.', [self::I18N_PREFIX, $entryId, 'description']),
            'input_schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'q' => [
                        'type' => 'string',
                        'title' => 'Search text',
                        'description' => 'Free text applied to the list search field, e.g. a record name.',
                    ],
                    'filters' => [
                        'type' => 'object',
                        'title' => 'Filters',
                        'description' => 'Declared filter keys only. Read the registered locate tab action schema for the exact keys and option values.',
                    ],
                    'sort' => [
                        'type' => 'string',
                        'title' => 'Sort',
                        'description' => 'Declared sort key; prefix with - for descending.',
                    ],
                ],
            ],
        ];
    }
}
