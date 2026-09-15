<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

use Nexia\Mutation\MutationMatcher;

/**
 * An opaque, contribution-owned navigation target, permissions, and optional
 * condition that tells a guided client when to re-evaluate the task.
 *
 * The host may expose the target, but must not parse it or reinterpret an App
 * target as a Core-internal frontend alias. Its representation remains owned
 * by the contribution so this framework-neutral DTO can cross the host/App
 * boundary without exposing routing internals.
 */
final readonly class SetupTaskAction
{
    /**
     * @param list<string> $requiredPermissionKeys
     * @param ?MutationMatcher $recheckAfter A committed-mutation hint that
     *                                       asks the host to re-evaluate the
     *                                       task; it is never completion proof.
     */
    public function __construct(
        public string $routeTarget,
        public array $requiredPermissionKeys = [],
        public ?MutationMatcher $recheckAfter = null,
    ) {
    }
}
