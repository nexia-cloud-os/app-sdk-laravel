<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/**
 * Explicitly identifies an unissued document revision for cancellation and
 * rebuild. A checksum is intentionally absent: no source may be attached.
 */
final readonly class PreReadySignableDocumentReference
{
    public function __construct(
        public string $publicId,
        public int $revision,
    ) {
        if ($publicId === '' || $publicId !== trim($publicId) || $revision < 1) {
            throw new InvalidArgumentException('Pre-ready signable document reference is invalid.');
        }
    }
}
