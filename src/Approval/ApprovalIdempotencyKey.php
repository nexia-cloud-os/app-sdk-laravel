<?php

declare(strict_types=1);

namespace Nexia\Approval;

use InvalidArgumentException;
use Nexia\Organization\Contracts\LegalEntity;

/**
 * Immutable idempotency operation identity. The host supplies its authorized
 * Legal Entity explicitly; Apps never derive it from an HTTP request.
 */
final readonly class ApprovalIdempotencyKey
{
    public function __construct(
        public LegalEntity $legalEntity,
        public ?string $value,
        public string $action,
        public string $fingerprint,
        public string $resourceType,
    ) {
        foreach (['action' => $action, 'fingerprint' => $fingerprint, 'resourceType' => $resourceType] as $field => $candidate) {
            if ($candidate === '' || $candidate !== trim($candidate)) {
                throw new InvalidArgumentException("Approval idempotency {$field} must be non-blank and normalized.");
            }
        }

        if ($value !== null && ($value === '' || $value !== trim($value))) {
            throw new InvalidArgumentException('Approval idempotency value must be null or a normalized non-blank string.');
        }
    }
}
