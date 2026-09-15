<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/**
 * Non-content field ownership projection from an immutable SignableDocument.
 * It deliberately excludes labels, geometry, values, and input guidance.
 */
final readonly class SignableDocumentResolvedField
{
    public function __construct(
        public string $key,
        public string $signatoryRoleKey,
        public bool $required,
        public ?int $participantSlot = null,
    ) {
        if ($key === '' || $key !== trim($key)
            || preg_match('/\A[a-z][a-z0-9_]*\z/D', $signatoryRoleKey) !== 1
            || ($participantSlot !== null && $participantSlot < 1)) {
            throw new InvalidArgumentException('Signable document resolved field is invalid.');
        }
    }
}
