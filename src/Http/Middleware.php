<?php

declare(strict_types=1);

namespace Nexia\Http;

final class Middleware
{
    public const AGENT_DELEGATION = 'agent.delegation';

    public const APP_INSTALLED = 'app.installed';

    public const AUTHENTICATED_LOCALE = 'nexia.locale';

    public const LEGAL_ENTITY_CONTEXT = 'nexia.context.legal_entity';

    public const WORKSPACE_CONTEXT = 'nexia.context.workspace';
}
