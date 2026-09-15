<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\ResourceImport\Recipe\ImportRecipe;

/**
 * What this file means, in resource keys and column names.
 *
 * A separate contract from the row policy and the decision provider because the
 * three answer different questions and an App may need only one of them. A
 * pipeline whose file maps to a single resource needs a recipe and neither of
 * the others.
 *
 * Given the settled decisions, because some of what the recipe says depends on
 * them: a grade's level comes from the ladder the person approved, and the
 * recipe is what states which attribute that value fills.
 */
interface ImportRecipeProvider
{
    /**
     * @param  array<string, mixed>  $decisions  decision key → the settled reply,
     *         in the shape that decision declared. Empty on the first pass, when
     *         the plan is being derived and nothing has been answered yet
     */
    public function recipe(array $decisions = []): ImportRecipe;
}
