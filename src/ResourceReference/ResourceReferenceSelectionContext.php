<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Organization\Contracts\OperatingUnit;

/** Core-restored context for a declared Resource Reference field. */
final readonly class ResourceReferenceSelectionContext
{
    public function __construct(
        public string $consumerResourceKey,
        public string $consumerField,
        public string $targetResourceKey,
        public Actor $actor,
        public ?LegalEntity $legalEntity,
        public DateTimeImmutable $asOf,
        public ?OperatingUnit $operatingUnit = null,
        public bool $forWrite = true,
    ) {
        if (! self::isResourceKey($consumerResourceKey) || ! self::isResourceKey($targetResourceKey)) {
            throw new InvalidArgumentException('Resource Reference selection requires canonical consumer and target Resource keys.');
        }

        if (mb_strlen($consumerField) > 160
            || preg_match('/\A[a-z][a-z0-9._-]*\z/D', $consumerField) !== 1) {
            throw new InvalidArgumentException('Resource Reference selection requires a canonical consumer field.');
        }
    }

    private static function isResourceKey(string $resourceKey): bool
    {
        $separator = strpos($resourceKey, '.');
        $appKey = $separator === false ? '' : substr($resourceKey, 0, $separator);

        return ResourceRef::hasCanonicalIdentity($appKey, $resourceKey);
    }
}
