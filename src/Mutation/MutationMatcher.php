<?php

declare(strict_types=1);

namespace Nexia\Mutation;

use InvalidArgumentException;

/**
 * App-neutral condition for rechecking state after a committed mutation.
 *
 * Keys within one matcher use OR semantics. Resource IDs also use OR semantics
 * and, when present, narrow a matching Resource Key. The operation and subject
 * filters must both match. Resource and action subjects are deliberately
 * separate so an operation can never acquire two meanings.
 */
final readonly class MutationMatcher
{
    private const KEY_PATTERN = '/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)+$/';

    /**
     * @param  list<string>  $resourceKeys Stable Resource Catalog keys.
     * @param  list<string>  $actionKeys Stable semantic action keys.
     * @param  list<MutationOperation>  $operations
     * @param  list<string>  $resourceIds Optional stable public resource IDs.
     */
    public function __construct(
        public array $resourceKeys = [],
        public array $actionKeys = [],
        public array $operations = [],
        public array $resourceIds = [],
    ) {
        if ($resourceKeys === [] && $actionKeys === []) {
            throw new InvalidArgumentException('A mutation matcher requires at least one resource or action key.');
        }

        if ($resourceKeys !== [] && $actionKeys !== []) {
            throw new InvalidArgumentException('A mutation matcher cannot mix resource and action keys.');
        }

        if ($operations === []) {
            throw new InvalidArgumentException('A mutation matcher requires at least one operation.');
        }

        $this->assertKeys($resourceKeys, 'resource');
        $this->assertKeys($actionKeys, 'action');

        foreach ($operations as $operation) {
            if (! $operation instanceof MutationOperation) {
                throw new InvalidArgumentException('Mutation matcher operations must be MutationOperation values.');
            }
        }

        $operationValues = array_map(
            static fn (MutationOperation $operation): string => $operation->value,
            $operations,
        );

        if ($resourceKeys !== [] && in_array(MutationOperation::Succeeded->value, $operationValues, true)) {
            throw new InvalidArgumentException('Resource mutation matchers cannot use the succeeded operation.');
        }

        if ($actionKeys !== [] && $operationValues !== [MutationOperation::Succeeded->value]) {
            throw new InvalidArgumentException('Action mutation matchers must use only the succeeded operation.');
        }

        if ($actionKeys !== [] && $resourceIds !== []) {
            throw new InvalidArgumentException('Action mutation matchers cannot name resource IDs.');
        }

        foreach ($resourceIds as $resourceId) {
            if (! is_string($resourceId) || trim($resourceId) === '' || strlen($resourceId) > 160) {
                throw new InvalidArgumentException('Mutation matcher resource IDs must be non-blank strings of at most 160 bytes.');
            }
        }

        if (count($resourceKeys) !== count(array_unique($resourceKeys))
            || count($actionKeys) !== count(array_unique($actionKeys))
            || count($operations) !== count(array_unique($operationValues))
            || count($resourceIds) !== count(array_unique($resourceIds))) {
            throw new InvalidArgumentException('Mutation matcher keys and operations must be unique.');
        }
    }

    /** @param list<string> $keys */
    private function assertKeys(array $keys, string $kind): void
    {
        foreach ($keys as $key) {
            if (! is_string($key)) {
                throw new InvalidArgumentException("Mutation matcher {$kind} keys must be strings.");
            }

            if (strlen($key) > 160 || preg_match(self::KEY_PATTERN, $key) !== 1) {
                throw new InvalidArgumentException("Mutation matcher {$kind} key [{$key}] is invalid.");
            }
        }
    }
}
