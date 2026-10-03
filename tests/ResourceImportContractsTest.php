<?php

declare(strict_types=1);

use Nexia\AsyncWork\BackgroundOperationReservation;
use Nexia\AsyncWork\Contracts\BackgroundOperationStore;
use Nexia\ResourceImport\Analysis\Contracts\ImportAnalysisStore;
use Nexia\ResourceImport\Analysis\ImportAnalysisResult;
use Nexia\ResourceImport\Analysis\ImportAnalysisSnapshot;
use Nexia\ResourceImport\Analysis\ImportRowSpool;
use Nexia\ResourceImport\Contracts\ImportRecipeProvider;
use Nexia\ResourceImport\Contracts\ImportFileCapabilityContribution;
use Nexia\ResourceImport\Contracts\ResourceImportPipeline;
use Nexia\ResourceImport\DataMigrationStageIdentity;
use Nexia\ResourceImport\Decision\ChoiceOption;
use Nexia\ResourceImport\Decision\MultipleChoiceDecision;
use Nexia\ResourceImport\Execution\Contracts\QueuedImportExecution;
use Nexia\ResourceImport\FillRuleProposal;
use Nexia\ResourceImport\ImportContributionValidator;
use Nexia\ResourceImport\ImportFileCapability;
use Nexia\ResourceImport\ImportRecipeDefinition;
use Nexia\ResourceImport\ImportSourceProfile;
use Nexia\ResourceImport\Plan\ImportPlan;
use Nexia\ResourceImport\ResourceImportPipelineDefinition;
use Nexia\ResourceImport\Workbook\Contracts\SafeWorkbookInspector;
use Nexia\ResourceTransfer\TransferSchema;

require __DIR__.'/register-source-autoload.php';

abstract class ResourceImportContractsRecipe implements ImportRecipeProvider {}

abstract class ResourceImportContractsPipeline implements ResourceImportPipeline {}

$customImportCapability = new class implements ImportFileCapabilityContribution
{
    public static function importFileCapabilities(): array
    {
        return [new ImportFileCapability(
            resourceKey: 'fixture.custom-register',
            permissionKeys: ['fixture.custom-register.import'],
            formats: ['csv'],
        )];
    }
};
assert($customImportCapability::importFileCapabilities()[0]->toArray() === [
    'resource_key' => 'fixture.custom-register',
    'permission_keys' => ['fixture.custom-register.import'],
    'formats' => ['csv'],
]);

function validRecipeDefinition(): ImportRecipeDefinition
{
    return new ImportRecipeDefinition(
        resourceKey: 'fixture.library_loans',
        schema: TransferSchema::make()
            ->key('loan_number', required: true)
            ->field('member_name', required: true),
        recipeClass: ResourceImportContractsRecipe::class,
        columnAliases: [
            'loan_number' => ['대출번호', 'Loan No'],
            'member_name' => ['회원명', 'Member Name'],
        ],
        permissionKeys: ['fixture.library_loan.import'],
        requestsCoreResources: ['directory.party'],
    );
}

function validPipelineDefinition(): ResourceImportPipelineDefinition
{
    return new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.loan_transaction',
        schema: TransferSchema::make()
            ->key('transaction_key', required: true)
            ->field('amount', type: 'decimal', required: true)
            ->field('currency', required: true),
        handlerClass: ResourceImportContractsPipeline::class,
        permissionKeys: ['fixture.loan_transaction.import'],
        fillRuleProposals: [new FillRuleProposal(
            key: 'currency',
            value: 'KRW',
            basisKey: 'fixture.loan_transaction.fill.currency.basis',
        )],
        columnAliases: ['transaction_key' => ['거래번호']],
        sourceProfiles: [new ImportSourceProfile(
            key: 'ecount',
            labelKey: 'fixture.import.sources.ecount',
            columnAliases: ['transaction_key' => ['거래번호']],
        )],
        templateExampleRows: [['transaction_key' => 'TX-001', 'amount' => 1200, 'currency' => 'KRW']],
        dataMigrationStage: new DataMigrationStageIdentity(
            stageKey: 'fixture.loan_transactions',
            providerKey: 'ecount',
            targetKey: 'fixture',
        ),
    );
}

function importContractMustFail(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException $exception) {
        if (! str_contains($exception->getMessage(), $message)) {
            throw new RuntimeException(
                "Expected failure containing [{$message}], got [{$exception->getMessage()}].",
            );
        }

        return;
    }

    throw new RuntimeException("Expected import contract failure containing [{$message}].");
}

ImportContributionValidator::portfolio(
    [validRecipeDefinition()],
    [validPipelineDefinition()],
);
assert(validPipelineDefinition()->templateExampleRows[0]['transaction_key'] === 'TX-001');

importContractMustFail(
    fn () => ImportContributionValidator::pipeline(new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.bad_template_example',
        schema: TransferSchema::make()->field('name'),
        handlerClass: ResourceImportContractsPipeline::class,
        templateExampleRows: [['unknown' => 'value']],
    )),
    'invalid template example value',
);

$spool = ImportRowSpool::create();
$spool->append(['line' => 2, 'values' => ['사번', 0, 1.5]]);
$spool->append(['line' => 3, 'values' => ['E001', null, true]]);
assert(count($spool) === 2);
$firstPass = iterator_to_array($spool, false);
$secondPass = iterator_to_array($spool, false);
assert($firstPass === $secondPass);
assert($firstPass[0]['values'][0] === '사번');
try {
    $spool->append(['line' => 4]);
    throw new RuntimeException('A sealed import row spool accepted another row.');
} catch (RuntimeException $exception) {
    assert($exception->getMessage() === 'Cannot append to a sealed import row spool.');
}

$analysis = new ImportAnalysisResult('xlsx', ['사번'], ['sheet' => '급여대장'], $spool);
$snapshot = new ImportAnalysisSnapshot(
    $analysis->format,
    $analysis->headers,
    $analysis->metadata,
    $analysis->rows,
);
assert($snapshot->rows === $spool);
assert(interface_exists(QueuedImportExecution::class));
assert(interface_exists(BackgroundOperationStore::class));
assert(interface_exists(ImportAnalysisStore::class));
assert(interface_exists(SafeWorkbookInspector::class));
$reservation = new BackgroundOperationReservation('operation-1', true, true);
assert($reservation->operationId === 'operation-1');
assert($reservation->acquired && $reservation->matchesIdentity);

$multipleChoice = new MultipleChoiceDecision(
    key: 'invite_people',
    labelKey: 'fixture.import.invite_people',
    options: [
        new ChoiceOption('2', 'fixture.import.person', ['name' => 'Ada']),
        new ChoiceOption('3', 'fixture.import.person', ['name' => 'Grace']),
    ],
    defaultValues: ['2'],
);
assert($multipleChoice->toArray()['type'] === MultipleChoiceDecision::TYPE);
assert($multipleChoice->toArray()['default_values'] === ['2']);

$referenceIssue = ['issue' => ['kind' => 'reference_not_found', 'reference_kind' => 'leave_type', 'value' => 'Annual'], 'count' => 42];
assert((new ImportPlan(referenceIssues: [$referenceIssue]))->toArray()['reference_issues'] === [$referenceIssue]);

importContractMustFail(
    fn () => ImportContributionValidator::pipeline(new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.bad_migration_stage',
        schema: TransferSchema::make()->field('name'),
        handlerClass: ResourceImportContractsPipeline::class,
        dataMigrationStage: new DataMigrationStageIdentity(
            stageKey: 'other.bad_stage',
            providerKey: 'ecount',
            targetKey: 'other',
        ),
    )),
    'outside its resource owner',
);

importContractMustFail(
    fn () => ImportContributionValidator::pipeline(new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.duplicate_profile',
        schema: TransferSchema::make()->field('name'),
        handlerClass: ResourceImportContractsPipeline::class,
        sourceProfiles: [
            new ImportSourceProfile('ecount', 'fixture.sources.ecount', ['name' => ['성명']]),
            new ImportSourceProfile('ecount', 'fixture.sources.ecount', ['name' => ['성명']]),
        ],
    )),
    'declared more than once',
);

importContractMustFail(
    fn () => ImportContributionValidator::recipe(new ImportRecipeDefinition(
        resourceKey: 'Fixture Library',
        schema: TransferSchema::make()->field('name'),
        recipeClass: ResourceImportContractsRecipe::class,
    )),
    'is invalid',
);

importContractMustFail(
    fn () => ImportContributionValidator::pipeline(new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.empty',
        schema: TransferSchema::make()->exportOnly('display_name'),
        handlerClass: ResourceImportContractsPipeline::class,
    )),
    'no importable columns',
);

importContractMustFail(
    fn () => ImportContributionValidator::pipeline(new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.versionless',
        schema: TransferSchema::make()->field('name'),
        handlerClass: ResourceImportContractsPipeline::class,
        schemaVersion: 0,
    )),
    'positive schema version',
);

importContractMustFail(
    fn () => ImportContributionValidator::recipe(new ImportRecipeDefinition(
        resourceKey: 'fixture.bad_alias',
        schema: TransferSchema::make()->field('name'),
        recipeClass: ResourceImportContractsRecipe::class,
        columnAliases: ['missing' => ['없는열']],
    )),
    'unknown importable column',
);

importContractMustFail(
    fn () => ImportContributionValidator::recipe(new ImportRecipeDefinition(
        resourceKey: 'fixture.ambiguous_alias',
        schema: TransferSchema::make()->field('first')->field('second'),
        recipeClass: ResourceImportContractsRecipe::class,
        columnAliases: ['first' => ['Name'], 'second' => ['name']],
    )),
    'claimed by both',
);

importContractMustFail(
    fn () => ImportContributionValidator::pipeline(new ResourceImportPipelineDefinition(
        resourceKey: 'fixture.bad_fill',
        schema: TransferSchema::make()->field('amount'),
        handlerClass: ResourceImportContractsPipeline::class,
        fillRuleProposals: [new FillRuleProposal(
            key: 'currency',
            value: 'KRW',
            basisKey: 'fixture.fill.currency.basis',
        )],
    )),
    'unknown importable column',
);

importContractMustFail(
    fn () => ImportContributionValidator::pipelines([
        validPipelineDefinition(),
        validPipelineDefinition(),
    ]),
    'declared more than once',
);

importContractMustFail(
    fn () => ImportContributionValidator::portfolio(
        [new ImportRecipeDefinition(
            resourceKey: 'fixture.same_key',
            schema: TransferSchema::make()->field('name'),
            recipeClass: ResourceImportContractsRecipe::class,
        )],
        [new ResourceImportPipelineDefinition(
            resourceKey: 'fixture.same_key',
            schema: TransferSchema::make()->field('name'),
            handlerClass: ResourceImportContractsPipeline::class,
        )],
    ),
    'both a recipe and a pipeline',
);

fwrite(STDOUT, "Resource Import contracts are valid.\n");
