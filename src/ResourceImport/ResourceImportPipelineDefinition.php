<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use Nexia\ResourceImport\Contracts\ResourceImportPipeline;
use Nexia\ResourceTransfer\TransferSchema;

/** One importable resource an App exposes, and the handler that commits it. */
final class ResourceImportPipelineDefinition
{
    /**
     * @param  string  $resourceKey  the Resource Key the host already knows
     * @param  TransferSchema  $schema  the fields this pipeline accepts.
     *                                  Reuses `TransferSchema` rather than introducing a second column
     *                                  shape so the host's header matching and ranking stay
     *                                  single-sourced across both commit paths.
     * @param  class-string<ResourceImportPipeline>  $handlerClass  resolved from
     *                                                              the container, so an adapter may inject the pipeline it wraps
     * @param  string  $profileScope  free-form namespace for `profile_key` values
     *                                this pipeline will accept, letting an app keep host-generated
     *                                format profiles distinct from ones its own integrations create
     * @param  list<string>  $permissionKeys  extra exact capabilities required on
     *                                        top of the host's bulk-data gate
     * @param  list<FillRuleProposal>  $fillRuleProposals  proposed values for
     *                                                     required fields no vendor file carries. Declared here because they
     *                                                     are domain policy: the host can see that a field is missing but has
     *                                                     no basis for deciding what belongs in it. Without these, a pipeline
     *                                                     requiring eleven fields against a file carrying four can be matched
     *                                                     perfectly and still never commit.
     * @param  list<string>  $requestsCoreResources  host-owned resource keys this
     *                                               pipeline may ask the host to create on its behalf, as a closed list.
     *
     *         A personnel row names a legal entity, an operating unit and a
     *         person before it names an employee, and none of those belong to
     *         the App that understands the row. Declaring them is what lets the
     *         host run the creation without the App reaching for a Core model —
     *         and declaring them *here*, beside the schema they serve, is what
     *         makes the request reviewable. A blanket App-wide grant would say
     *         only that an App touches accounts somewhere; this says which
     *         import does, and for what.
     *
     *         A declaration is a request, not a grant. The host still refuses
     *         unless the resource itself declares how one is created, the actor
     *         holds the capability to create one, and the key names a host
     *         resource rather than another App's — an App may never reach into a
     *         third App this way. Leaving this empty is the safe default and
     *         means the pipeline creates nothing outside its own resources.
     * @param  array<string, list<string>>  $columnAliases  schema key → the
     *                                                      headings a vendor file actually prints for it, as literal text.
     *
     *         They exist because the deterministic ladder cannot bridge a
     *         product term to a vendor term by measurement, and a file whose
     *         required columns go unmatched falls through to the model rung.
     *         Declared here rather than found on the handler by method name: an
     *         App should not have to guess the spelling of a method nothing
     *         declares
     * @param  list<ImportSourceProfile>  $sourceProfiles  explicit vendor/ERP
     *                                                     workbook shapes. When present,
     *                                                     the caller must choose one and
     *                                                     its aliases replace the legacy
     *                                                     definition-wide aliases.
     * @param  int  $schemaVersion  incremented when a stored mapping or preview
     *                              must no longer be replayed against this definition
     * @param  bool  $requiresDecisionBoundPreview  decisions can change a
     *                                              balance, monetary result, or access outcome, so the client must
     *                                              preview the settled replies and Core binds them into the approval
     *                                              proof before commit
     * @param  DataMigrationStageIdentity|null  $dataMigrationStage  server-owned
     *                                                                 Setup evidence identity recorded only after this pipeline applies
     */
    public function __construct(
        public readonly string $resourceKey,
        public readonly TransferSchema $schema,
        public readonly string $handlerClass,
        public readonly string $profileScope = 'ai',
        public readonly array $permissionKeys = [],
        public readonly array $fillRuleProposals = [],
        public readonly array $requestsCoreResources = [],
        public readonly array $columnAliases = [],
        public readonly array $sourceProfiles = [],
        public readonly int $schemaVersion = 1,
        /** Require the uploaded header row to equal the declared template headers, including order. */
        public readonly bool $requiresExactTemplateHeaders = false,
        /** Match only literal declared aliases; leave every other uploaded header unmatched. */
        public readonly bool $matchesDeclaredHeadersOnly = false,
        /** Bind settled decisions into the server-side preview proof before any write may start. */
        public readonly bool $requiresDecisionBoundPreview = false,
        /** Record successful executions as evidence for this declared migration stage. */
        public readonly ?DataMigrationStageIdentity $dataMigrationStage = null,
    ) {}
}
