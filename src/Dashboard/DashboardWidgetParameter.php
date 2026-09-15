<?php

declare(strict_types=1);

namespace Nexia\Dashboard;

use InvalidArgumentException;

final class DashboardWidgetParameter
{
    public const TYPE_STRING = 'string';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';

    private const VALID_TYPES = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
    ];

    /**
     * @param  list<mixed>|null  $enum
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $description,
        public readonly ?array $enum = null,
        public readonly mixed $default = null,
        public readonly ?string $target = null,
    ) {
        if ($name === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException(
                "DashboardWidgetParameter name '{$name}' must be lowercase alphanumeric/underscore",
            );
        }

        if (! in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                "DashboardWidgetParameter {$name} has invalid type {$type}",
            );
        }

        if ($enum !== null && $enum === []) {
            throw new InvalidArgumentException(
                "DashboardWidgetParameter {$name} enum cannot be empty when set",
            );
        }

        if ($target !== null && ! preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$/', $target)) {
            throw new InvalidArgumentException(
                "DashboardWidgetParameter {$name} target '{$target}' must be a "
                .'dotted path of lowercase alphanumeric/underscore identifiers',
            );
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $row = [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
        ];

        if ($this->enum !== null) {
            $row['enum'] = array_values($this->enum);
        }

        if ($this->default !== null) {
            $row['default'] = $this->default;
        }

        if ($this->target !== null) {
            $row['target'] = $this->target;
        }

        return $row;
    }
}
