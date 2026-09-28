<?php

declare(strict_types=1);

namespace Nexia\Http;

final class Middleware
{
    /** Host-authenticated App request entry; the host owns tenant/session restoration. */
    public const APP_REQUEST = 'nexia.app.request';

    public const AGENT_DELEGATION = 'agent.delegation';

    public const APP_INSTALLED = 'app.installed';

    public const AUTHENTICATED_LOCALE = 'nexia.locale';

    public const LEGAL_ENTITY_CONTEXT = 'nexia.context.legal_entity';

    public const WORKSPACE_CONTEXT = 'nexia.context.workspace';
}
