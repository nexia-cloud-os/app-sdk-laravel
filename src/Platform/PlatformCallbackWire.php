<?php

declare(strict_types=1);

namespace Nexia\Platform;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Approval\ApprovalWire;
use Nexia\Approval\Domain\ApprovalLineDefinition;
use Nexia\Approval\Resolver\{ResolverContext, ResolvedApprovalLine, ResolutionError, ResolutionErrorCode};
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Process\{ProcessWorkActionInvocation, ProcessWorkActionResult, ProcessWorkActionSuspension, ProcessUserTaskSubmissionInvocation, ProcessUserTaskSubmissionResult};
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\{SignatureDocumentDataQuery, SignatureDocumentDataPurpose, SignatureDocumentDataResult, SignatureDocumentDataStatus, SignatureDocumentDataCandidate, SignatureDocumentDataResolvedItem, SignatureDocumentDataDiagnostic, SignatureDocumentDataDiagnosticCode};
use Nexia\Tenancy\Contracts\TenantIdentity;
use Nexia\SelfService\{SelfWorkContextQuery, SelfServiceActionQuery, SelfWorkContextOption, SelfServiceActionItem};

/** Closed DTO wire for authenticated host-to-owner calls, never a PHP class selector. */
final class PlatformCallbackWire
{
    public static function actor(Actor $actor): array
    {
        return ['key' => $actor->key(), 'public_id' => $actor->publicId(), 'display_label' => $actor->displayLabel(),
            'party_key' => $actor->partyKey(), 'verified' => $actor->isVerified()];
    }

    /** Public domain rejection only; never serialize exception messages or host preparation state. */
    public static function error(\Throwable $error): ?array
    {
        return match (true) {
            $error instanceof \Nexia\Approval\Domain\ApprovalException => ['domain' => 'approval', ...$error->toErrorPayload()],
            $error instanceof \Nexia\Process\Domain\ProcessStartException => ['domain' => 'process', 'code' => $error->errorCode],
            $error instanceof \Nexia\Process\Domain\ProcessWorkActionException => ['domain' => 'process_work', 'code' => $error->errorCode],
            $error instanceof \Nexia\Signature\SignatureException => ['domain' => 'signature', 'code' => $error->errorCode->value, 'params' => $error->context],
            default => null,
        };
    }

    public static function restoreError(array $error): \Throwable
    {
        $code = $error['code'] ?? null;
        if (! is_string($code) && $code !== null) throw new InvalidArgumentException('Invalid platform error code.');
        $params = $error['params'] ?? [];
        if (! is_array($params)) throw new InvalidArgumentException('Invalid platform error parameters.');
        return match ($error['domain'] ?? null) {
            'approval' => \Nexia\Approval\Domain\ApprovalException::operationFailed('Approval operation failed.', $code ?? 'operation_failed', $params),
            'process' => new \Nexia\Process\Domain\ProcessStartException('Process operation failed.', $code ?? 'operation_failed'),
            'process_work' => new \Nexia\Process\Domain\ProcessWorkActionException('Process work action failed.', $code),
            'signature' => new \Nexia\Signature\SignatureException(\Nexia\Signature\SignatureErrorCode::from($code ?? ''), 'Signature operation failed.', $params),
            default => throw new InvalidArgumentException('Unknown platform error domain.'),
        };
    }

    public static function entity(LegalEntity $entity): array
    {
        return ['key' => $entity->key(), 'public_id' => $entity->publicId(), 'display_label' => $entity->displayLabel(),
            'code' => $entity->code(), 'party_public_id' => $entity->partyPublicId(), 'active' => $entity->isActiveOrganization()];
    }

    public static function input(ProcessWorkActionInvocation|ProcessUserTaskSubmissionInvocation|ResolverContext|SignatureDocumentDataQuery|SelfWorkContextQuery|SelfServiceActionQuery $value): array
    {
        $data = get_object_vars($value);
        foreach ($data as $key => $item) {
            if ($item instanceof Actor) $data[$key] = self::actor($item);
            elseif ($item instanceof LegalEntity) $data[$key] = self::entity($item);
            elseif ($item instanceof ResourceRef) $data[$key] = $item->toArray();
            elseif ($item instanceof TenantIdentity) $data[$key] = $item->key();
            elseif ($item instanceof DateTimeImmutable) $data[$key] = $item->format(DATE_ATOM);
            elseif ($item instanceof \BackedEnum) $data[$key] = $item->value;
            elseif ($item instanceof ApprovalLineDefinition) $data[$key] = $item->toSnapshot();
        }
        foreach (['resourceInputs', 'derivedSubjectAnchors', 'selectedResourceRefs'] as $key) {
            if (isset($data[$key])) $data[$key] = self::references($data[$key], false);
        }
        return $data;
    }

    public static function restoreInput(string $method, array $data, Actor $actor, ?LegalEntity $entity, ?Actor $subjectActor = null, ?TenantIdentity $tenant = null): ProcessWorkActionInvocation|ProcessUserTaskSubmissionInvocation|ResolverContext|SignatureDocumentDataQuery|SelfWorkContextQuery|SelfServiceActionQuery
    {
        $actorKey = $method === 'approval.resolve' ? 'submitter' : 'actor';
        if (($data[$actorKey]['public_id'] ?? null) !== $actor->publicId() || ($data['legalEntity']['public_id'] ?? null) !== $entity?->publicId()) {
            throw new InvalidArgumentException('Callback identity differs.');
        }
        $data[$actorKey] = $actor;
        $data['legalEntity'] = $entity;
        if ($method === 'self.contexts') return new SelfWorkContextQuery(...$data);
        if ($method === 'self.actions') {
            if (isset($data['workContext'])) $data['workContext'] = ResourceRef::fromArray($data['workContext']);
            return new SelfServiceActionQuery(...$data);
        }
        if ($entity === null) throw new InvalidArgumentException('Callback requires a legal entity.');
        foreach (['resourceRef', 'originResourceRef', 'subjectResourceRef', 'explicitSourceRef'] as $key) {
            if (isset($data[$key])) $data[$key] = ResourceRef::fromArray($data[$key]);
        }
        foreach (['resourceInputs', 'derivedSubjectAnchors', 'selectedResourceRefs'] as $key) {
            if (isset($data[$key])) $data[$key] = self::references($data[$key], true);
        }
        if ($method === 'approval.resolve') {
            $data['subjectActor'] = $subjectActor;
            return new ResolverContext(...$data);
        }
        if ($method === 'process.work') {
            $data['approvalLine'] = ApprovalWire::line($data['approvalLine']);
            return new ProcessWorkActionInvocation(...$data);
        }
        if ($method === 'process.user_task') return new ProcessUserTaskSubmissionInvocation(...$data);
        if ($method !== 'signature.data' || $tenant === null || (string) $data['tenant'] !== (string) $tenant->key()) {
            throw new InvalidArgumentException('Unsupported callback or tenant.');
        }
        $data['tenant'] = $tenant;
        $data['purpose'] = SignatureDocumentDataPurpose::from($data['purpose']);
        $data['asOf'] = new DateTimeImmutable($data['asOf']);
        return new SignatureDocumentDataQuery(...$data);
    }

    private static function references(array $values, bool $restore): array
    {
        return array_map(static function ($value) use ($restore) {
            if ($value === null) return null;
            if ($value instanceof ResourceRef) return $value->toArray();
            if ($restore && is_array($value) && isset($value['resource_key'])) return ResourceRef::fromArray($value);
            if (! is_array($value)) throw new InvalidArgumentException('Invalid callback reference.');
            return self::references($value, $restore);
        }, $values);
    }

    /** Signature values are only for the authenticated protected-data transport. Never log this payload. */
    public static function result(ProcessWorkActionResult|ProcessUserTaskSubmissionResult|ResolvedApprovalLine|SignatureDocumentDataResult|array $result): array
    {
        if (is_array($result)) return array_map(static function (SelfWorkContextOption|SelfServiceActionItem $item): array {
            $row = get_object_vars($item);
            if ($item instanceof SelfWorkContextOption) {
                return [...$row, 'resource' => $item->resource->toArray(), 'legalEntity' => $item->legalEntity->publicId()];
            }
            return [...$row, 'stage' => $item->stage->value, 'dueAt' => $item->dueAt?->format(DATE_ATOM),
                'returnTarget' => [...get_object_vars($item->returnTarget), 'workContext' => $item->returnTarget->workContext?->toArray()]];
        }, $result);
        if ($result instanceof ProcessWorkActionResult) return ['output' => $result->output, 'suspension' => $result->suspension?->toArray()];
        if ($result instanceof ProcessUserTaskSubmissionResult) return ['output' => $result->output];
        if ($result instanceof ResolvedApprovalLine) return ['line' => $result->isResolved() ? ApprovalLineDefinition::fromStages($result->stages())->toSnapshot() : null,
            'errors' => array_map(static fn ($error) => [...get_object_vars($error), 'code' => $error->code->value], $result->errors())];
        return ['status' => $result->status->value,
            'candidates' => array_map(static fn ($candidate) => $candidate->toArray(), $result->candidates()),
            'items' => array_map(static fn ($item) => ['resource_ref' => $item->resourceRef->toArray(), 'source_revision' => $item->sourceRevision,
                'content_hash' => $item->contentHash, 'effective_at' => $item->effectiveAt?->format(DATE_ATOM),
                'values' => $item->protectedValues(), 'display_values' => $item->displayValues()], $result->resolvedItems()),
            'diagnostics' => array_map(static fn ($diagnostic) => $diagnostic->toArray(), $result->diagnostics())];
    }

    public static function restoreResult(string $method, array $data): ProcessWorkActionResult|ProcessUserTaskSubmissionResult|ResolvedApprovalLine|SignatureDocumentDataResult|array
    {
        if (in_array($method, ['self.contexts', 'self.actions'], true)) return $data;
        if ($method === 'process.user_task') return new ProcessUserTaskSubmissionResult(...$data);
        if ($method === 'process.work') {
            $s = $data['suspension'];
            return new ProcessWorkActionResult($data['output'], $s === null ? null : new ProcessWorkActionSuspension($s['kind'], $s['topic'], $s['reference_id'], $s['phase'], $s['metadata']));
        }
        if ($method === 'approval.resolve') {
            return $data['line'] !== null ? ResolvedApprovalLine::resolved(ApprovalWire::line($data['line'])->stages())
                : ResolvedApprovalLine::failed(array_map(static fn (array $error) => new ResolutionError(...[...$error, 'code' => ResolutionErrorCode::from($error['code'])]), $data['errors']));
        }
        if ($method !== 'signature.data') throw new InvalidArgumentException('Unsupported callback result.');
        return new SignatureDocumentDataResult(SignatureDocumentDataStatus::from($data['status']),
            array_map(static fn (array $c) => new SignatureDocumentDataCandidate(ResourceRef::fromArray($c['resource_ref']), $c['source_revision'], $c['content_hash'], $c['effective_at'] === null ? null : new DateTimeImmutable($c['effective_at'])), $data['candidates']),
            array_map(static fn (array $i) => new SignatureDocumentDataResolvedItem(ResourceRef::fromArray($i['resource_ref']), $i['source_revision'], $i['content_hash'],
                $i['effective_at'] === null ? null : new DateTimeImmutable($i['effective_at']), $i['values'], $i['display_values']), $data['items']),
            array_map(static fn (array $d) => new SignatureDocumentDataDiagnostic(SignatureDocumentDataDiagnosticCode::from($d['code']), $d['retry_after_seconds']), $data['diagnostics']));
    }
}
