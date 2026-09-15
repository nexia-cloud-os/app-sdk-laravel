<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

final readonly class SignatureTemplateCatalogQuery
{
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public string $locale,
        public string $effectiveOn,
    ) {
        foreach ([$bindingKey, $bindingVersion, $locale] as $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException('Signature template catalog query contains a blank identity.');
            }
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveOn);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $effectiveOn) {
            throw new InvalidArgumentException('Signature template catalog effective date is invalid.');
        }
    }
}
