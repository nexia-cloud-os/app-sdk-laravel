<?php

declare(strict_types=1);

namespace Nexia\Signature;

use Nexia\Signature\Contracts\SignatureDocumentPlanSource;
use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Composable, App-neutral document plan shared by template and uploaded-PDF sources. */
final readonly class SignatureDocumentPlanSubmission
{
    /** @param list<SignatureTrustedAssetSelection> $trustedAssets */
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public string $locale,
        public string $effectiveOn,
        public SignatureDocumentPlanSource $source,
        public array $trustedAssets,
        public string $idempotencyKey,
        public string $correlationId,
        public ?string $causationId = null,
    ) {
        foreach (['bindingKey' => $bindingKey, 'bindingVersion' => $bindingVersion, 'locale' => $locale, 'idempotencyKey' => $idempotencyKey, 'correlationId' => $correlationId] as $name => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature document plan {$name} must be normalized and non-blank.");
            }
        }
        if (strlen($bindingKey) > 191
            || strlen($bindingVersion) > 32
            || strlen($locale) > 20
            || strlen($idempotencyKey) > 191) {
            throw new InvalidArgumentException('Signature document plan contains an oversized identity.');
        }
        if (! array_is_list($trustedAssets)) {
            throw new InvalidArgumentException('Signature document plan trusted assets must be a list.');
        }
        $placements = [];
        foreach ($trustedAssets as $asset) {
            if (! $asset instanceof SignatureTrustedAssetSelection || isset($placements[$asset->placementKey])) {
                throw new InvalidArgumentException('Signature document plan trusted assets are invalid.');
            }
            $placements[$asset->placementKey] = true;
        }
        $uuid = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match($uuid, $correlationId) !== 1
            || ($causationId !== null && preg_match($uuid, $causationId) !== 1)
            || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,2}\z/D', $locale) !== 1) {
            throw new InvalidArgumentException('Signature document plan identity is invalid.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveOn);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $effectiveOn) {
            throw new InvalidArgumentException('Signature document plan effectiveOn must be an ISO date.');
        }
    }
}
