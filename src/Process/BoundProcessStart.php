<?php

declare(strict_types=1);

namespace Nexia\Process;

use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** A backend-authorized request to start the Process bound to one App intent. */
final readonly class BoundProcessStart
{
    /** @param array<string, mixed> $variables */
    public function __construct(
        public string $bindingKey,
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $resourceRef,
        public array $variables,
        public string $idempotencyKey,
        public ?string $businessKey = null,
        public ?string $name = null,
        public ?string $correlationId = null,
    ) {
        if ($bindingKey === '' || $bindingKey !== trim($bindingKey)
            || $idempotencyKey === '' || $idempotencyKey !== trim($idempotencyKey)) {
            throw new \InvalidArgumentException('Bound Process start identifiers must be non-blank and normalized.');
        }
    }
}
