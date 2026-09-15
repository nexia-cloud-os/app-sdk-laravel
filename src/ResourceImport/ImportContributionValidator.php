<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use Nexia\ResourceImport\Contracts\ResourceImportPipeline;
use InvalidArgumentException;
use Nexia\ResourceImport\Contracts\ImportDecisionProvider;
use Nexia\ResourceImport\Contracts\ImportRecipeProvider;
use Nexia\ResourceImport\Contracts\ImportRowPolicy;
use Nexia\ResourceTransfer\TransferSchema;

/**
 * Definition checks an App can run without loading the host.
 *
 * Ownership and installation are deliberately absent: only the host can prove
 * which package contributed a class and whether a tenant has that App active.
 * Everything intrinsic to the definition belongs here so an App's own CI can
 * reject a typo before the host discovers it at runtime.
 */
final class ImportContributionValidator
{
    /** @param list<ImportRecipeDefinition> $definitions */
    public static function recipes(array $definitions): void
    {
        self::uniqueDefinitions($definitions, ImportRecipeDefinition::class, 'recipe');

        foreach ($definitions as $definition) {
            self::recipe($definition);
        }
    }

    /** @param list<ResourceImportPipelineDefinition> $definitions */
    public static function pipelines(array $definitions): void
    {
        self::uniqueDefinitions($definitions, ResourceImportPipelineDefinition::class, 'pipeline');

        foreach ($definitions as $definition) {
            self::pipeline($definition);
        }
    }

    /**
     * Validate both App-owned tracks and refuse a key contributed through both.
     *
     * @param  list<ImportRecipeDefinition>  $recipes
     * @param  list<ResourceImportPipelineDefinition>  $pipelines
     */
    public static function portfolio(array $recipes, array $pipelines): void
    {
        self::recipes($recipes);
        self::pipelines($pipelines);

        $recipeKeys = array_fill_keys(array_map(
            static fn (ImportRecipeDefinition $definition): string => $definition->resourceKey,
            $recipes,
        ), true);

        foreach ($pipelines as $definition) {
            if (isset($recipeKeys[$definition->resourceKey])) {
                throw new InvalidArgumentException(
                    "Resource import key [{$definition->resourceKey}] is declared as both a recipe and a pipeline.",
                );
            }
        }
    }

    public static function recipe(ImportRecipeDefinition $definition): void
    {
        self::common(
            resourceKey: $definition->resourceKey,
            schema: $definition->schema,
            profileScope: $definition->profileScope,
            permissionKeys: $definition->permissionKeys,
            fillRuleProposals: $definition->fillRuleProposals,
            requestsCoreResources: $definition->requestsCoreResources,
            columnAliases: $definition->columnAliases,
            sourceProfiles: [],
            schemaVersion: $definition->schemaVersion,
        );

        if (! is_a($definition->recipeClass, ImportRecipeProvider::class, true)) {
            throw new InvalidArgumentException(
                "Import recipe provider [{$definition->recipeClass}] must implement ImportRecipeProvider.",
            );
        }

        if ($definition->rowPolicyClass !== null
            && ! is_a($definition->rowPolicyClass, ImportRowPolicy::class, true)) {
            throw new InvalidArgumentException(
                "Import row policy [{$definition->rowPolicyClass}] must implement ImportRowPolicy.",
            );
        }

        if ($definition->decisionProviderClass !== null
            && ! is_a($definition->decisionProviderClass, ImportDecisionProvider::class, true)) {
            throw new InvalidArgumentException(
                "Import decision provider [{$definition->decisionProviderClass}] must implement ImportDecisionProvider.",
            );
        }
    }

    public static function pipeline(ResourceImportPipelineDefinition $definition): void
    {
        self::common(
            resourceKey: $definition->resourceKey,
            schema: $definition->schema,
            profileScope: $definition->profileScope,
            permissionKeys: $definition->permissionKeys,
            fillRuleProposals: $definition->fillRuleProposals,
            requestsCoreResources: $definition->requestsCoreResources,
            columnAliases: $definition->columnAliases,
            sourceProfiles: $definition->sourceProfiles,
            schemaVersion: $definition->schemaVersion,
        );

        if (! is_a($definition->handlerClass, ResourceImportPipeline::class, true)) {
            throw new InvalidArgumentException(
                "Resource import handler [{$definition->handlerClass}] must implement ResourceImportPipeline.",
            );
        }

        self::dataMigrationStage($definition);
    }

    /**
     * @param  list<string>  $permissionKeys
     * @param  list<FillRuleProposal>  $fillRuleProposals
     * @param  list<string>  $requestsCoreResources
     * @param  array<string, list<string>>  $columnAliases
     * @param  list<ImportSourceProfile>  $sourceProfiles
     */
    private static function common(
        string $resourceKey,
        TransferSchema $schema,
        string $profileScope,
        array $permissionKeys,
        array $fillRuleProposals,
        array $requestsCoreResources,
        array $columnAliases,
        array $sourceProfiles,
        int $schemaVersion,
    ): void {
        self::resourceKey($resourceKey, 'Resource import key');

        if (trim($profileScope) === '') {
            throw new InvalidArgumentException("Resource import [{$resourceKey}] must declare a profile scope.");
        }

        if ($schemaVersion < 1) {
            throw new InvalidArgumentException(
                "Resource import [{$resourceKey}] must declare a positive schema version.",
            );
        }

        if (! $schema->hasColumns()) {
            throw new InvalidArgumentException("Resource import [{$resourceKey}] declares no columns.");
        }

        $columns = [];
        foreach ($schema->columns() as $column) {
            $columns[(string) $column['key']] = $column;
        }

        $importable = array_filter(
            $columns,
            static fn (array $column): bool => ($column['importable'] ?? false) === true,
        );

        if ($importable === []) {
            throw new InvalidArgumentException("Resource import [{$resourceKey}] declares no importable columns.");
        }

        self::permissions($resourceKey, $permissionKeys);
        self::coreRequests($resourceKey, $requestsCoreResources);
        self::aliases($resourceKey, $columnAliases, $importable);
        self::sourceProfiles($resourceKey, $sourceProfiles, $importable);
        self::fillRules($resourceKey, $fillRuleProposals, $importable);
    }

    /**
     * @param  list<ImportSourceProfile>  $profiles
     * @param  array<string, true>  $importable
     */
    private static function sourceProfiles(string $resourceKey, array $profiles, array $importable): void
    {
        $keys = [];

        foreach ($profiles as $profile) {
            if (! $profile instanceof ImportSourceProfile) {
                throw new InvalidArgumentException(
                    "Resource import pipeline [{$resourceKey}] has an invalid source profile declaration.",
                );
            }

            $key = trim($profile->key);
            if ($key === '' || preg_match('/^[a-z0-9][a-z0-9._-]*$/', $key) !== 1) {
                throw new InvalidArgumentException(
                    "Resource import pipeline [{$resourceKey}] source profile key [{$profile->key}] is invalid.",
                );
            }
            if (isset($keys[$key])) {
                throw new InvalidArgumentException(
                    "Resource import pipeline [{$resourceKey}] source profile [{$key}] is declared more than once.",
                );
            }
            if (trim($profile->labelKey) === '') {
                throw new InvalidArgumentException(
                    "Resource import pipeline [{$resourceKey}] source profile [{$key}] has no label key.",
                );
            }

            self::aliases($resourceKey.'.'.$key, $profile->columnAliases, $importable);
            $keys[$key] = true;
        }
    }

    /** @param list<string> $permissionKeys */
    private static function permissions(string $resourceKey, array $permissionKeys): void
    {
        $seen = [];

        foreach ($permissionKeys as $permission) {
            if (! is_string($permission) || ! self::isResourceKey($permission)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Resource import [%s] declares invalid permission key [%s].',
                        $resourceKey,
                        is_scalar($permission) ? (string) $permission : get_debug_type($permission),
                    ),
                );
            }

            if (isset($seen[$permission])) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] declares permission [{$permission}] more than once.",
                );
            }

            $seen[$permission] = true;
        }
    }

    /** @param list<string> $requests */
    private static function coreRequests(string $resourceKey, array $requests): void
    {
        $seen = [];

        foreach ($requests as $request) {
            if (! is_string($request)) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] declares a non-string Core resource request.",
                );
            }

            self::resourceKey($request, "Core resource requested by [{$resourceKey}]");

            if (isset($seen[$request])) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] requests Core resource [{$request}] more than once.",
                );
            }

            $seen[$request] = true;
        }
    }

    /**
     * @param  array<string, list<string>>  $aliases
     * @param  array<string, array<string, mixed>>  $importable
     */
    private static function aliases(string $resourceKey, array $aliases, array $importable): void
    {
        $claimed = [];

        foreach ($aliases as $columnKey => $values) {
            if (! is_string($columnKey) || ! isset($importable[$columnKey])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Resource import [%s] declares aliases for unknown importable column [%s].',
                        $resourceKey,
                        (string) $columnKey,
                    ),
                );
            }

            if (! is_array($values)) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] aliases for [{$columnKey}] must be a list.",
                );
            }

            foreach ($values as $alias) {
                if (! is_string($alias) || trim($alias) === '') {
                    throw new InvalidArgumentException(
                        "Resource import [{$resourceKey}] declares an empty alias for [{$columnKey}].",
                    );
                }

                $normalized = function_exists('mb_strtolower')
                    ? mb_strtolower(trim($alias), 'UTF-8')
                    : strtolower(trim($alias));

                if (isset($claimed[$normalized])) {
                    throw new InvalidArgumentException(
                        "Resource import [{$resourceKey}] alias [{$alias}] is claimed by both [{$claimed[$normalized]}] and [{$columnKey}].",
                    );
                }

                $claimed[$normalized] = $columnKey;
            }
        }
    }

    /**
     * @param  list<FillRuleProposal>  $proposals
     * @param  array<string, array<string, mixed>>  $importable
     */
    private static function fillRules(string $resourceKey, array $proposals, array $importable): void
    {
        $seen = [];

        foreach ($proposals as $proposal) {
            if (! $proposal instanceof FillRuleProposal) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] returned a non-FillRuleProposal entry.",
                );
            }

            if (! isset($importable[$proposal->key])) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] proposes a value for unknown importable column [{$proposal->key}].",
                );
            }

            if (isset($seen[$proposal->key])) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] proposes column [{$proposal->key}] more than once.",
                );
            }

            if ($proposal->basisKey === null || trim($proposal->basisKey) === '') {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] fill rule [{$proposal->key}] must state its basis key.",
                );
            }

            if (trim($proposal->group) === '') {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] fill rule [{$proposal->key}] must name a group.",
                );
            }

            if ($proposal->sameAs !== null && ! isset($importable[$proposal->sameAs])) {
                throw new InvalidArgumentException(
                    "Resource import [{$resourceKey}] fill rule [{$proposal->key}] follows unknown column [{$proposal->sameAs}].",
                );
            }

            if ($proposal->requiresUniformColumn !== null) {
                $conditionKey = $proposal->requiresUniformColumn['key'] ?? null;

                if (! is_string($conditionKey) || ! isset($importable[$conditionKey])) {
                    throw new InvalidArgumentException(
                        "Resource import [{$resourceKey}] fill rule [{$proposal->key}] conditions on an unknown column.",
                    );
                }
            }

            $seen[$proposal->key] = true;
        }
    }

    /**
     * @param  array<mixed>  $definitions
     * @param  class-string  $definitionClass
     */
    private static function uniqueDefinitions(array $definitions, string $definitionClass, string $track): void
    {
        $seen = [];

        foreach ($definitions as $definition) {
            if (! $definition instanceof $definitionClass) {
                throw new InvalidArgumentException(
                    "Resource import {$track} list contains [".get_debug_type($definition)."] instead of [{$definitionClass}].",
                );
            }

            if (isset($seen[$definition->resourceKey])) {
                throw new InvalidArgumentException(
                    "Resource import {$track} key [{$definition->resourceKey}] is declared more than once.",
                );
            }

            $seen[$definition->resourceKey] = true;
        }
    }

    private static function resourceKey(string $value, string $label): void
    {
        if (! self::isResourceKey($value)) {
            throw new InvalidArgumentException("{$label} [{$value}] is invalid.");
        }
    }

    private static function isResourceKey(string $value): bool
    {
        return preg_match('/^[a-z][a-z0-9_-]*(?:\.[a-z][a-z0-9_-]*)+$/', $value) === 1;
    }

    private static function dataMigrationStage(ResourceImportPipelineDefinition $definition): void
    {
        $identity = $definition->dataMigrationStage;
        if ($identity === null) {
            return;
        }

        $resourceOwner = explode('.', $definition->resourceKey, 2)[0];
        if ($identity->targetKey !== $resourceOwner
            || ! str_starts_with($identity->stageKey, $identity->targetKey.'.')) {
            throw new InvalidArgumentException(
                "Resource import [{$definition->resourceKey}] declares a data migration stage outside its resource owner.",
            );
        }
    }
}
