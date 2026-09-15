<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;

/**
 * An App-owned operation on one Resource.
 *
 * It describes the operation already implemented by the owning App. Hosts use
 * this to discover and invoke a Resource action without minting a separate
 * integration contract for every action.
 */
final readonly class ResourceActionDescriptor
{
    /**
     * @param  array<string, mixed>|null  $inputSchema
     */
    public function __construct(
        public string $key,
        public string $permission,
        public string $method,
        public string $path,
        public ?array $inputSchema = null,
        public ResourceActionEffect $effect = ResourceActionEffect::Mutate,
        public ?string $labelKey = null,
        public ?string $description = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*$/', $key) !== 1) {
            throw new InvalidArgumentException('Resource action key must be a lowercase dotted identifier.');
        }
        if (preg_match('/^[a-z][a-z0-9-]*\.[a-z][a-z0-9._-]*$/', $permission) !== 1) {
            throw new InvalidArgumentException('Resource action permission must be a valid permission key.');
        }

        if ($method !== strtoupper($method)
            || ! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new InvalidArgumentException('Resource action method is unsupported.');
        }
        if (! str_starts_with($path, '/api/') || str_contains($path, '?') || str_contains($path, '#')) {
            throw new InvalidArgumentException('Resource action path must be an absolute API route template.');
        }
        if ($inputSchema !== null && array_is_list($inputSchema)) {
            throw new InvalidArgumentException('Resource action input schema must be a JSON object.');
        }
        if ($effect === ResourceActionEffect::Read && $method === 'DELETE') {
            throw new InvalidArgumentException('A read Resource action cannot use DELETE.');
        }
        if ($description !== null && trim($description) === '') {
            throw new InvalidArgumentException('Resource action description cannot be empty.');
        }
    }
}
