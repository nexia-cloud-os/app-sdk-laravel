<?php

declare(strict_types=1);

namespace Nexia\Platform;

use InvalidArgumentException;
use Nexia\Signature\SignatureBulkBindingLifecycleContext;
use Nexia\Signature\SignableDocumentReference;
use Nexia\Signature\SignatureParticipantSnapshot;
use Nexia\Signature\SignatureConsentPolicyReference;
use Nexia\Signature\SignatureExpiryPolicy;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\SignatureBulkBindingExecutionContext;
use Nexia\Signature\SignatureBulkBindingExecutionPlan;
use Nexia\Signature\SignatureBulkBindingPreviewContext;
use Nexia\Signature\SignatureBulkGroupSelection;
use Nexia\Signature\SignatureBulkParticipantSetting;
use Nexia\Signature\SignatureBulkRequestedParticipantAssignment;
use Nexia\Signature\SignatureBulkTemplateParticipantAssignment;
use Nexia\Signature\SignatureBulkTemplateProvenance;
use Nexia\Tenancy\Contracts\TenantIdentity;

/** Protected host-to-App payload, never a browser or logging representation. */
final class SignatureBulkCallbackWire
{
    public static function input(SignatureBulkBindingPreviewContext|SignatureBulkBindingExecutionContext|SignatureBulkBindingLifecycleContext $context): array
    {
        if ($context instanceof SignatureBulkBindingLifecycleContext) {
            return [
                'legalEntity' => PlatformCallbackWire::entity($context->legalEntity),
                'actor' => PlatformCallbackWire::actor($context->actor),
                'execution' => self::input($context->execution), 'operationId' => $context->operationId,
                'phase' => $context->phase, 'document' => $context->document?->toArray(),
                'sourcePageCount' => $context->sourcePageCount, 'requestPublicId' => $context->requestPublicId,
                'participants' => array_map(static fn ($participant) => $participant->toArray(), $context->participants),
                'consent' => $context->consent?->toArray(), 'expiry' => $context->expiry?->toArray(),
            ];
        }
        $common = [
            'tenant' => $context->tenant->key(),
            'legalEntity' => PlatformCallbackWire::entity($context->legalEntity),
            'actor' => PlatformCallbackWire::actor($context->actor),
            'template' => $context->template->toArray(),
        ];
        if ($context instanceof SignatureBulkBindingPreviewContext) {
            return [...$common,
                'selection' => $context->selection->toArray(),
                'templateAssignments' => array_map(static fn ($item) => $item->toArray(), $context->templateAssignments),
                'requestedAssignments' => array_map(static fn ($item) => $item->toArray(), $context->requestedAssignments),
                'participantSettings' => array_map(static fn ($item) => $item->toArray(), $context->participantSettings),
            ];
        }

        return [...$common,
            'frozenSubject' => $context->frozenSubject->toArray(),
            'frozenSemanticFingerprint' => $context->frozenSemanticFingerprint,
            'frozenPlan' => $context->frozenPlan->toArray(),
            'protectedPlan' => $context->frozenPlan->protectedPayload(),
            'operationId' => $context->operationId,
        ];
    }

    public static function restore(string $method, array $data, Actor $actor, ?LegalEntity $entity, ?TenantIdentity $tenant): SignatureBulkBindingPreviewContext|SignatureBulkBindingExecutionContext|SignatureBulkBindingLifecycleContext
    {
        if ($method === 'signature.bulk.lifecycle') {
            SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
                'legalEntity', 'actor', 'execution', 'operationId', 'phase', 'document', 'sourcePageCount', 'requestPublicId', 'participants', 'consent', 'expiry',
            ]);
            return new SignatureBulkBindingLifecycleContext(
                self::restore('signature.bulk.reauthorize', $data['execution'], $actor, $entity, $tenant),
                $data['operationId'], $data['phase'],
                $data['document'] === null ? null : SignableDocumentReference::fromArray($data['document']),
                $data['sourcePageCount'], $data['requestPublicId'],
                array_map(SignatureParticipantSnapshot::fromArray(...), $data['participants']),
                $data['consent'] === null ? null : SignatureConsentPolicyReference::fromArray($data['consent']),
                $data['expiry'] === null ? null : SignatureExpiryPolicy::fromArray($data['expiry']),
            );
        }
        if ($entity === null || $tenant === null
            || (string) ($data['tenant'] ?? '') !== (string) $tenant->key()
            || ($data['actor']['public_id'] ?? null) !== $actor->publicId()
            || ($data['legalEntity']['public_id'] ?? null) !== $entity->publicId()) {
            throw new InvalidArgumentException('Signature bulk callback identity differs.');
        }
        $template = SignatureBulkTemplateProvenance::fromArray($data['template']);
        if ($method === 'signature.bulk.preview') {
            SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
                'tenant', 'legalEntity', 'actor', 'template', 'selection',
                'templateAssignments', 'requestedAssignments', 'participantSettings',
            ]);

            return new SignatureBulkBindingPreviewContext(
                $tenant, $entity, $actor, $template,
                SignatureBulkGroupSelection::fromArray($data['selection']),
                array_map(SignatureBulkTemplateParticipantAssignment::fromArray(...), $data['templateAssignments']),
                array_map(SignatureBulkRequestedParticipantAssignment::fromArray(...), $data['requestedAssignments']),
                array_map(SignatureBulkParticipantSetting::fromArray(...), $data['participantSettings']),
            );
        }
        if ($method !== 'signature.bulk.reauthorize') {
            throw new InvalidArgumentException('Unsupported signature bulk callback.');
        }
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'tenant', 'legalEntity', 'actor', 'template', 'frozenSubject',
            'frozenSemanticFingerprint', 'frozenPlan', 'protectedPlan', 'operationId',
        ]);

        return new SignatureBulkBindingExecutionContext(
            $tenant, $entity, $actor, $template,
            ResourceRef::fromArray($data['frozenSubject']),
            $data['frozenSemanticFingerprint'],
            SignatureBulkBindingExecutionPlan::fromArray($data['frozenPlan'], $data['protectedPlan']),
            $data['operationId'],
        );
    }
}
