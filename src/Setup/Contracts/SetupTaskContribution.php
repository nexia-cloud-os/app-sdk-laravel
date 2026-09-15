<?php

declare(strict_types=1);

namespace Nexia\Setup\Contracts;

use Nexia\Setup\Data\SetupTaskDefinition;

/**
 * Declares the Setup tasks owned by one installed App.
 *
 * Contributions are declarations only. The host discovers, validates, and
 * registers them; contributors do not receive host state or persistence
 * services through this contract.
 */
interface SetupTaskContribution
{
    /**
     * @return list<SetupTaskDefinition>
     */
    public function tasks(): array;
}
