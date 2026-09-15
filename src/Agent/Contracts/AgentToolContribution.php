<?php

declare(strict_types=1);

namespace Nexia\Agent\Contracts;

use Nexia\Agent\AgentToolDeclaration;

/**
 * App-owned, route-backed Agent tools discovered from contribution locations.
 *
 * Implementations are static declarations. The owning Laravel route remains
 * authoritative for validation and authorization; Core only publishes the
 * immutable metadata and checks that exact binding at Agent execution time.
 */
interface AgentToolContribution
{
    /** @return list<AgentToolDeclaration> */
    public static function agentTools(): array;
}
