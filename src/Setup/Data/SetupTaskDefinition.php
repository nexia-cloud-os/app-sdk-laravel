<?php

declare(strict_types=1);

namespace Nexia\Setup\Data;

use Nexia\Setup\Contracts\SetupTaskEvaluator;
use Nexia\Setup\Enums\SetupTaskCompletionMode;
use Nexia\Setup\Enums\SetupTaskImportance;

/**
 * Immutable, framework-neutral Setup task declaration contributed by an App.
 *
 * The host owns declaration discovery, validation, ordering, evaluation
 * scheduling, authorization, persistence, and resulting task status. This
 * DTO deliberately carries none of that host runtime state.
 */
final readonly class SetupTaskDefinition
{
    /**
     * @param  list<string>  $dependencies  Stable task keys that must precede this task.
     * @param  class-string<SetupTaskEvaluator>  $evaluatorClass
     */
    public function __construct(
        public string $key,
        public int $revision,
        public int $priority,
        public string $titleKey,
        public string $descriptionKey,
        public SetupTaskImportance $importance,
        public SetupTaskCompletionMode $completionMode,
        public bool $skippable,
        public bool $requiresAcknowledgement,
        public array $dependencies,
        public string $evaluatorClass,
        public ?SetupTaskAction $defaultAction = null,
        /** Optional reusable Guide launched by this task; it never determines completion. */
        public ?string $guideKey = null,
        /** Optional grouping inside the owning App. App Family and App identity are host-derived. */
        public ?SetupTaskSection $section = null,
        /** Optional stable subject supplied to the evaluator for host-generated task instances. */
        public ?string $subjectKey = null,
        /** Optional localized guidance describing when an operator may skip this task. */
        public ?string $skipGuidanceKey = null,
    ) {}
}
