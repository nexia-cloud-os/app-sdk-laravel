<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use InvalidArgumentException;
use Nexia\Attachments\AttachmentTarget;
use Nexia\ResourceTransfer\TransferSchema;
use Nexia\Support\CanonicalPayloadFingerprint;

/** Data-only pipeline metadata and results; handler classes never cross the boundary. */
final class ImportPipelineWire
{
    public const WORK_CALLBACKS = [
        'import.pipeline.input', 'import.pipeline.rows', 'import.pipeline.result',
        'organization.read', 'resource.read', 'settings.read',
        'person.profile.write', 'party.precondition', 'organization.precondition',
        'attachment.exists', 'attachment.file', 'attachment.attach', 'attachment.has_any',
        'selection.resolve', 'selection.options', 'selection.resolve_write',
        'login_invitation.invite', 'audit.record',
    ];

    public static function workKey(string $appKey): string { return $appKey.'.resource_import'; }

    public const METHODS = ['import.receive', 'import.validate', 'import.apply', 'import.retry', 'import.plan'];

    public static function definition(ResourceImportPipelineDefinition $definition): array
    {
        ImportContributionValidator::pipeline($definition);
        $data = get_object_vars($definition);
        unset($data['handlerClass']);
        $data['schema'] = $definition->schema->toArray();
        $data['fillRuleProposals'] = array_map(get_object_vars(...), $definition->fillRuleProposals);
        $data['sourceProfiles'] = array_map(get_object_vars(...), $definition->sourceProfiles);
        $data['dataMigrationStage'] = $definition->dataMigrationStage === null ? null : get_object_vars($definition->dataMigrationStage);

        return $data;
    }

    public static function restoreDefinition(array $data, string $handler): ResourceImportPipelineDefinition
    {
        $wire = $data;
        self::assertShape($data, ResourceImportPipelineDefinition::class, ['handlerClass']);
        if (array_key_exists('handlerClass', $data)) {
            throw new InvalidArgumentException('Remote pipeline handlers are not executable metadata.');
        }
        $data['schema'] = TransferSchema::fromArray($data['schema']);
        $data['fillRuleProposals'] = array_map(static fn (array $row) => new FillRuleProposal(...$row), $data['fillRuleProposals']);
        $data['sourceProfiles'] = array_map(static fn (array $row) => new ImportSourceProfile(...$row), $data['sourceProfiles']);
        $data['dataMigrationStage'] = $data['dataMigrationStage'] === null ? null : new DataMigrationStageIdentity(...$data['dataMigrationStage']);
        $data['handlerClass'] = $handler;
        $definition = new ResourceImportPipelineDefinition(...$data);
        ImportContributionValidator::pipeline($definition);
        if (CanonicalPayloadFingerprint::sha256($wire) !== CanonicalPayloadFingerprint::sha256(self::definition($definition))) {
            throw new InvalidArgumentException('Invalid pipeline metadata shape.');
        }

        return $definition;
    }

    public static function state(ImportBatchState $state): array
    {
        return [...get_object_vars($state),
            'stage' => $state->stage->value,
            'attachmentTarget' => $state->attachmentTarget === null ? null : get_object_vars($state->attachmentTarget),
            'rowDispositions' => array_map(get_object_vars(...), $state->rowDispositions),
        ];
    }

    public static function restoreState(array $data): ImportBatchState
    {
        self::assertShape($data, ImportBatchState::class);
        $data['stage'] = ImportStage::from($data['stage']);
        $data['attachmentTarget'] = $data['attachmentTarget'] === null ? null : new AttachmentTarget(...$data['attachmentTarget']);
        $data['rowDispositions'] = array_map(static fn (array $row) => new ImportRowDisposition(...$row), $data['rowDispositions']);
        foreach (['rowCount', 'acceptedCount', 'rejectedCount'] as $key) {
            if (! is_int($data[$key]) || $data[$key] < 0) {
                throw new InvalidArgumentException('Invalid pipeline result counts.');
            }
        }

        if ($data['skippedCount'] !== null && (! is_int($data['skippedCount']) || $data['skippedCount'] < 0)) {
            throw new InvalidArgumentException('Invalid skipped row count.');
        }
        foreach (['normalizedRows', 'rejectedRows', 'rowDispositions'] as $key) {
            if (! is_array($data[$key]) || ! array_is_list($data[$key])) {
                throw new InvalidArgumentException('Pipeline result rows must be lists.');
            }
        }
        foreach ($data['dispositionCounts'] as $count) {
            if (! is_int($count) || $count < 0) {
                throw new InvalidArgumentException('Invalid disposition count.');
            }
        }

        return new ImportBatchState(...$data);
    }
    private static function assertShape(array $data, string $class, array $excluded = []): void
    {
        $keys = array_diff(array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            (new \ReflectionClass($class))->getConstructor()->getParameters()), $excluded);
        if (array_diff($keys, array_keys($data)) !== [] || array_diff(array_keys($data), $keys) !== []) {
            throw new InvalidArgumentException('Invalid pipeline wire fields.');
        }
    }
}
