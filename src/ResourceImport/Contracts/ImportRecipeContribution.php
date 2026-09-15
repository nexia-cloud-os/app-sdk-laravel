<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

/**
 * App contribution for an import the host drives from the App's recipe.
 *
 * The sibling of {@see ResourceImportPipelineContribution}, and the difference
 * is who writes. A pipeline contribution hands the host a commit to call; this
 * hands the host a description of what the file means and lets the host do the
 * writing through each resource's own declared create action.
 *
 * An App with its own batch table, state machine and idempotency wants the
 * pipeline. An App whose import is a sequence over distinct values — across
 * resources it does not all own — wants this one, because a pipeline would
 * force it to name classes on the other side of the boundary.
 *
 * Both are discovered the same way and land in the same candidate list. A
 * resource key belongs to exactly one of them; contributing it twice is refused
 * rather than resolved, because picking one silently is how a row lands
 * somewhere nobody chose.
 */
interface ImportRecipeContribution
{
    /** @return list<ImportRecipeDefinition> */
    public static function resourceImportRecipes(): array;
}
