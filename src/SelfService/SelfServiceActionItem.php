<?php

declare(strict_types=1);

namespace Nexia\SelfService;

use DateTimeImmutable;
use InvalidArgumentException;

/** Safe, actor-owned current work item contributed to My Profile. */
final readonly class SelfServiceActionItem
{
    public function __construct(
        public string $key,
        public string $category,
        public string $title,
        public string $summary,
        public SelfServiceActionStage $stage,
        public SelfServiceReturnTarget $returnTarget,
        public int $priority = 50,
        public ?DateTimeImmutable $dueAt = null,
    ) {
        if (preg_match('/\A[a-z][a-z0-9-]*\.[a-z][a-z0-9._-]*\z/D', $key) !== 1
            || trim($category) === ''
            || trim($title) === ''
            || trim($summary) === ''
            || $priority < 0
            || $priority > 100) {
            throw new InvalidArgumentException('A self-service action item requires safe display metadata and a priority from 0 to 100.');
        }
    }
}
