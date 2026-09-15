<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** App-authorized lookup for the exact published template assignment policy. */
final readonly class SignatureTemplateParticipantAssignmentQuery
{
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public string $templateKey,
        public string $locale,
        public string $effectiveOn,
    ) {
        foreach ([$bindingKey, $bindingVersion, $templateKey, $locale] as $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException('Signature template participant assignment query contains a blank identity.');
            }
        }
        if (strlen($bindingKey) > 191
            || strlen($bindingVersion) > 32
            || strlen($templateKey) > 160
            || strlen($locale) > 20
            || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,2}\z/D', $locale) !== 1) {
            throw new InvalidArgumentException('Signature template participant assignment query is invalid.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveOn);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $effectiveOn) {
            throw new InvalidArgumentException('Signature template participant assignment query effectiveOn must be an ISO date.');
        }
    }
}
