<?php

declare(strict_types=1);

namespace Nexia\Approval;

use InvalidArgumentException;
use Nexia\Approval\Domain\ApprovalLineDefinition;
use Nexia\Approval\Domain\ApprovalLineStepDefinition;
use Nexia\Approval\Domain\Enums\ApprovalStepKind;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/** Exact DTO conversion for the isolated host adapter, not PHP object serialization. */
final class ApprovalWire
{
    public static function submission(BoundApprovalSubmission|CurrentBoundApprovalSubmission $submission): array
    {
        return [...get_object_vars($submission), 'legalEntity' => $submission->legalEntity->publicId(),
            'drafter' => $submission->drafter->publicId(), 'resourceRef' => $submission->resourceRef->toArray(),
            'lineDefinition' => $submission->lineDefinition?->toSnapshot()];
    }

    public static function restoreSubmission(array $data, LegalEntity $entity, Actor $actor, bool $current): BoundApprovalSubmission|CurrentBoundApprovalSubmission
    {
        if (($data['legalEntity'] ?? null) !== $entity->publicId() || ($data['drafter'] ?? null) !== $actor->publicId()) {
            throw new InvalidArgumentException('Approval submission context differs.');
        }
        $data['legalEntity'] = $entity;
        $data['drafter'] = $actor;
        $data['resourceRef'] = ResourceRef::fromArray($data['resourceRef']);
        $data['lineDefinition'] = self::line($data['lineDefinition']);
        return $current ? new CurrentBoundApprovalSubmission(...$data) : new BoundApprovalSubmission(...$data);
    }

    public static function line(?array $snapshot): ?ApprovalLineDefinition
    {
        if ($snapshot === null) return null;
        if (! array_is_list($snapshot) || count($snapshot) > 100) throw new InvalidArgumentException('Invalid approval line.');
        return ApprovalLineDefinition::fromStages(array_map(static function (array $stage): array {
            if (! is_array($stage['steps'] ?? null) || ! array_is_list($stage['steps']) || count($stage['steps']) > 100) {
                throw new InvalidArgumentException('Invalid approval stage.');
            }
            return array_map(static fn (array $step) => new ApprovalLineStepDefinition(
                ApprovalStepKind::from($step['kind']), $step['user_id'], $step['is_final_authority'] ?? false,
            ), $stage['steps']);
        }, $snapshot));
    }

    public static function result(CurrentBoundApprovalResult $result): array
    {
        return [...get_object_vars($result), 'case' => $result->case->publicId,
            'route' => $result->route === null ? null : get_object_vars($result->route),
            'lineDefinition' => $result->lineDefinition?->toSnapshot()];
    }

    public static function restoreResult(array $data): CurrentBoundApprovalResult
    {
        $data['case'] = new ApprovalCaseReference($data['case']);
        $data['route'] = $data['route'] === null ? null : new ApprovalRouteReference(...$data['route']);
        $data['lineDefinition'] = self::line($data['lineDefinition']);
        return new CurrentBoundApprovalResult(...$data);
    }
}
