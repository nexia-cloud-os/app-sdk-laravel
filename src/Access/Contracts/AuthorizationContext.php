<?php

declare(strict_types=1);

namespace Nexia\Access\Contracts;

use Nexia\Permission\AssignmentScope;

/** Executes a callback under an explicit authorization target restored by Core. */
interface AuthorizationContext
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(AssignmentScope $scope, ?int $scopeId, callable $callback): mixed;
}
