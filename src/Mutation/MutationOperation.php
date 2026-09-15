<?php

declare(strict_types=1);

namespace Nexia\Mutation;

/**
 * A committed domain change that can wake a narrow browser-side waiter.
 *
 * `Succeeded` is reserved for semantic actions that do not map to one
 * Resource Catalog model. Resource-backed changes use the lifecycle values.
 */
enum MutationOperation: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Succeeded = 'succeeded';
}
