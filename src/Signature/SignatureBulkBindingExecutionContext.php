<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Tenancy\Contracts\TenantIdentity;

/** Core-restored mutable authority plus the exact approved bulk plan. */
final readonly class SignatureBulkBindingExecutionContext
{
    public function __construct(
        public TenantIdentity $tenant,
        public LegalEntity $legalEntity,
        public Actor $actor,
        public SignatureBulkTemplateProvenance $template,
        public ResourceRef $frozenSubject,
        public string $frozenSemanticFingerprint,
        public SignatureBulkBindingExecutionPlan $frozenPlan,
    ) {
        if ($template->bindingKey !== $frozenPlan->bindingKey
            || $template->bindingVersion !== $frozenPlan->bindingVersion
            || $template->templateKey !== $frozenPlan->templateKey
            || SignatureBulkGroupSelection::resourceIdentity($frozenSubject)
                !== SignatureBulkGroupSelection::resourceIdentity($frozenPlan->subject)
            || ! hash_equals($frozenPlan->semanticFingerprint, $frozenSemanticFingerprint)) {
            throw new InvalidArgumentException('Signature bulk execution context does not match its frozen template, subject, and plan.');
        }
    }
}
