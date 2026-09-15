<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** App-authorized values submitted to the host without exposing App persistence. */
final readonly class CurrentBoundSignableDocumentSubmission
{
    /**
     * @param  array<string, mixed>  $variables
     * @param  array<string, list<array<string, scalar|null>>>  $signatoryRoles
     * @param  list<SignatureTrustedAssetSelection>  $trustedAssets
     */
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public string $templateKey,
        public string $locale,
        public string $effectiveOn,
        public array $variables,
        public array $signatoryRoles,
        public string $idempotencyKey,
        public string $correlationId,
        public ?string $causationId = null,
        public array $trustedAssets = [],
    ) {
        foreach ([
            'bindingKey' => $bindingKey,
            'bindingVersion' => $bindingVersion,
            'templateKey' => $templateKey,
            'locale' => $locale,
            'idempotencyKey' => $idempotencyKey,
            'correlationId' => $correlationId,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signable document submission {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($bindingKey) > 191
            || strlen($bindingVersion) > 32
            || strlen($templateKey) > 160
            || strlen($locale) > 20
            || strlen($idempotencyKey) > 191
            || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,2}\z/D', $locale) !== 1) {
            throw new InvalidArgumentException('Signable document submission contains an oversized or invalid identity.');
        }

        $uuidPattern = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match($uuidPattern, $correlationId) !== 1
            || ($causationId !== null && preg_match($uuidPattern, $causationId) !== 1)) {
            throw new InvalidArgumentException('Signable document correlation and causation identities must be UUIDs.');
        }

        if ($causationId !== null && ($causationId === '' || $causationId !== trim($causationId))) {
            throw new InvalidArgumentException('Signable document submission causationId must be null or normalized and non-blank.');
        }

        if (! array_is_list($trustedAssets)) {
            throw new InvalidArgumentException('Signable document trusted assets must be a list.');
        }
        $placements = [];
        foreach ($trustedAssets as $asset) {
            if (! $asset instanceof SignatureTrustedAssetSelection || isset($placements[$asset->placementKey])) {
                throw new InvalidArgumentException('Signable document trusted assets are invalid.');
            }
            $placements[$asset->placementKey] = true;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveOn);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $effectiveOn) {
            throw new InvalidArgumentException('Signable document submission effectiveOn must be an ISO date.');
        }

        // Bindings with fixed body content legitimately declare no variables.
        // Core validates the submitted object against the descriptor, so an
        // empty list is accepted here only as that empty transport object;
        // unknown non-empty values still fail at the host boundary.
        if (($variables !== [] && array_is_list($variables))
            || array_is_list($signatoryRoles)
            || $signatoryRoles === []) {
            throw new InvalidArgumentException('Signable document variables and signatory roles must be keyed snapshots, with at least one signatory role.');
        }

        foreach ($signatoryRoles as $roleKey => $subjects) {
            if (! is_string($roleKey)
                || preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1
                || ! is_array($subjects)
                || ! array_is_list($subjects)) {
                throw new InvalidArgumentException('Signable document signatory-role snapshots must be keyed lists.');
            }

            foreach ($subjects as $subject) {
                if (! is_array($subject) || array_is_list($subject)) {
                    throw new InvalidArgumentException("Signable document role [{$roleKey}] entries must be objects.");
                }
                foreach ($subject as $key => $value) {
                    if (! is_string($key) || (! is_scalar($value) && $value !== null)) {
                        throw new InvalidArgumentException("Signable document role [{$roleKey}] entries must contain scalar snapshots only.");
                    }
                }
            }
        }
    }
}
