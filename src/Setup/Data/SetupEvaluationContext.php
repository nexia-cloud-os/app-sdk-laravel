<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

use DateTimeImmutable;

/**
 * Framework-neutral identity and time context supplied by the host for one
 * setup task evaluation.
 *
 * Tenant and actor keys are opaque host identifiers. The evaluator must not
 * treat them as persistence models or assume a particular key representation.
 */
final readonly class SetupEvaluationContext
{
    public function __construct(
        public int|string $tenantKey,
        public int|string $actorKey,
        public string $locale,
        public DateTimeImmutable $evaluatedAt,
        /** Stable definition key for the task currently being evaluated. */
        public ?string $taskKey = null,
        /** Optional definition subject, such as an App key for a generated Core task. */
        public ?string $subjectKey = null,
    ) {
    }
}
