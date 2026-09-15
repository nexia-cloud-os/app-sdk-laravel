<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;

/** Exact request schemas for an owning Resource's ordinary create/update APIs. */
final readonly class ResourceMutationDescriptor
{
    /**
     * @param  array<string, mixed>|null  $createInputSchema
     * @param  array<string, mixed>|null  $updateInputSchema
     */
    public function __construct(
        public ?array $createInputSchema = null,
        public ?array $updateInputSchema = null,
    ) {
        foreach (['create' => $createInputSchema, 'update' => $updateInputSchema] as $action => $schema) {
            if ($schema !== null && array_is_list($schema)) {
                throw new InvalidArgumentException("Resource {$action} input schema must be a JSON object.");
            }
        }
    }
}
