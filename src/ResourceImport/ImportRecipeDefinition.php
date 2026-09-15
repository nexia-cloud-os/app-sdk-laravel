<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use Nexia\ResourceTransfer\TransferSchema;

/**
 * One importable resource an App exposes as a recipe rather than a pipeline.
 *
 * The difference from {@see ResourceImportPipelineDefinition} is who runs the
 * commit. A pipeline says "hand me the rows and I will write them"; a recipe
 * says "here is what the file means, you write them". Both are legitimate — an
 * App with its own batch table, its own state machine and its own idempotency
 * wants the first, and an App whose import is a sequence over distinct values
 * across resources it does not all own wants the second.
 *
 * A recipe names resource keys and column names and nothing else, so an App
 * can state what a cross-resource file means without naming host model
 * classes. Core resolves the keys and runs each Resource's declared action.
 *
 * What the host owns in return: the upload, the parsing, the header matching,
 * the batch, the approval proof, the idempotency key, the creation order, the
 * transaction, and the three gates in front of every write. An App handing over
 * data cannot reach past any of them.
 */
final class ImportRecipeDefinition
{
    /**
     * @param  string  $resourceKey  the Resource Key the host already knows
     * @param  TransferSchema  $schema  the fields this import accepts. Reuses
     *                                  `TransferSchema` rather than introducing a second column shape, so
     *                                  header matching and ranking stay single-sourced across every
     *                                  commit path
     * @param  class-string<Contracts\ImportRecipeProvider>  $recipeClass  resolved
     *                                                                     from the container
     * @param  class-string<Contracts\ImportRowPolicy>|null  $rowPolicyClass  which
     *                                                                        rows this import refuses. Null when the schema's own required
     *                                                                        fields are the whole answer
     * @param  class-string<Contracts\ImportDecisionProvider>|null  $decisionProviderClass
     *                                                                                      what a person still has to settle. Null when nothing does
     * @param  array<string, list<string>>  $columnAliases  schema key → the
     *                                                      headings a vendor file actually prints for it.
     *
     *         Literal text rather than i18n keys: a Korean HR export writes `사번`
     *         whoever is reading it. They exist because the deterministic ladder
     *         cannot bridge a product term to a vendor term by measurement —
     *         `사번` against `인력 번호` scores 0.583 and `입사일` against `시작일`
     *         0.694, both under the embedding threshold, and the threshold cannot
     *         come down because a genuinely wrong pair sits at 0.597. Without
     *         them four required columns went unmatched and every personnel file
     *         fell through to the model rung.
     *
     *         A declared field rather than a method the host looks for by name.
     *         The convention worked while one host-owned pipeline used it and
     *         stops being defensible the moment an App has to guess the spelling
     *         of a method nothing declares
     * @param  string  $profileScope  free-form namespace for `profile_key` values
     * @param  list<string>  $permissionKeys  extra exact capabilities required on
     *                                        top of the host's bulk-data gate.
     *
     *         Only capabilities the host can decide before reading the file. One
     *         scoped to an operating unit cannot go here — which units a file
     *         names is not known until its rows are read, and an actor may hold
     *         it for one and not another. Those are decided at the write instead,
     *         which is why the write has its own check
     * @param  list<FillRuleProposal>  $fillRuleProposals  proposed values for
     *                                                     required fields no vendor file carries
     * @param  list<string>  $requestsCoreResources  host-owned resource keys this
     *                                               recipe may ask the host to create, as a closed list. A request, not
     *                                               a grant: the host still refuses unless the resource published a
     *                                               create action and the actor holds the capability, and no
     *                                               declaration reaches another App's resources
     * @param  int  $schemaVersion  incremented when a stored mapping or preview
     *                              must no longer be replayed against this definition
     */
    public function __construct(
        public readonly string $resourceKey,
        public readonly TransferSchema $schema,
        public readonly string $recipeClass,
        public readonly ?string $rowPolicyClass = null,
        public readonly ?string $decisionProviderClass = null,
        public readonly array $columnAliases = [],
        public readonly string $profileScope = 'ai',
        public readonly array $permissionKeys = [],
        public readonly array $fillRuleProposals = [],
        public readonly array $requestsCoreResources = [],
        public readonly int $schemaVersion = 1,
    ) {}
}
