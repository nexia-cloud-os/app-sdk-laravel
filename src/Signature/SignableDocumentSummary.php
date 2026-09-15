<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Minimal non-content projection safe for an authorized adopting App. */
final readonly class SignableDocumentSummary
{
    /**
     * @param  list<SignableDocumentResolvedField>  $resolvedFields
     * @param  list<SignatureTrustedAssetSnapshot>  $trustedAssets
     */
    public function __construct(
        public string $publicId,
        public SignableDocumentStatus $status,
        public int $revision,
        public string $bindingKey,
        public string $bindingVersion,
        public string $templateKey,
        public int $templateVersion,
        public int $sourcePageCount,
        public array $resolvedFields,
        public ?string $checksum,
        public ?string $failureCode,
        public int $renderAttempts,
        public array $trustedAssets = [],
    ) {
        if ($publicId === '' || $publicId !== trim($publicId) || $revision < 1
            || $bindingKey === '' || $bindingVersion === '' || $templateKey === ''
            || $templateVersion < 1 || $sourcePageCount < 1 || $renderAttempts < 0
            || ! array_is_list($resolvedFields)
            || ! array_is_list($trustedAssets)
            || ($checksum === null && $trustedAssets !== [])) {
            throw new InvalidArgumentException('Signable document summary is invalid.');
        }
        foreach ($resolvedFields as $field) {
            if (! $field instanceof SignableDocumentResolvedField) {
                throw new InvalidArgumentException('Signable document summary fields are invalid.');
            }
        }
        foreach ($trustedAssets as $asset) {
            if (! $asset instanceof SignatureTrustedAssetSnapshot
                || ($checksum !== null && ! hash_equals($checksum, $asset->outputChecksum))) {
                throw new InvalidArgumentException('Signable document summary trusted assets are invalid.');
            }
        }
        if ($checksum !== null && preg_match('/\A[0-9a-f]{64}\z/D', $checksum) !== 1) {
            throw new InvalidArgumentException('Signable document summary checksum is invalid.');
        }
    }
}
