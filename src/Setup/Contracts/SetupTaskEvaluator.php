<?php

declare(strict_types=1);

namespace Nexia\Setup\Contracts;

use Nexia\Setup\Data\SetupEvaluationContext;
use Nexia\Setup\Data\SetupTaskAssessment;

/** Evaluates one declared setup task against the host-supplied identity context. */
interface SetupTaskEvaluator
{
    public function evaluate(SetupEvaluationContext $context): SetupTaskAssessment;
}
