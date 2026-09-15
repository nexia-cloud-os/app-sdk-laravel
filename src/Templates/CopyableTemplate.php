<?php

declare(strict_types=1);

namespace Nexia\Templates;

use InvalidArgumentException;

final readonly class CopyableTemplate
{
    public const SCOPE_TENANT = 'tenant';

    public const SCOPE_LEGAL_ENTITY = 'legal_entity';

    public function __construct(
        public string $app,
        public string $key,
        public string $version,
        public string $scope,
        public mixed $source,
    ) {
        if ($app === '' || $key === '' || $version === '') {
            throw new InvalidArgumentException('A copyable template requires an app, key, and version.');
        }

        if (! in_array($scope, [self::SCOPE_TENANT, self::SCOPE_LEGAL_ENTITY], true)) {
            throw new InvalidArgumentException("Unsupported copyable template scope [{$scope}].");
        }
    }
}
