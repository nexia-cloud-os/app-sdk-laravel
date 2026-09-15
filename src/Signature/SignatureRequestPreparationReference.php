<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Opaque handle for one App-started request-local editing session. */
final readonly class SignatureRequestPreparationReference
{
    public function __construct(
        public string $publicId,
        public int $revision,
    ) {
        if ($publicId === '' || $publicId !== trim($publicId) || $revision < 1) {
            throw new InvalidArgumentException('Signature request preparation reference is invalid.');
        }
    }
}
