<?php

declare(strict_types=1);

namespace Nexia\Testing\Contracts;

use Nexia\AppDescriptors\ApprovalDocumentSchema;
use Nexia\AppDescriptors\ApprovalFormBindingDescriptor;
use Nexia\Approval\ApprovalRouteReference;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Testing\PublishedApprovalTemplate;
use Nexia\Testing\HostTestRecord;

/** Integration-test setup surface; hosts bind it only for test environments. */
interface ApprovalTestHost
{
    /** @param array<string, mixed> $attributes */
    public function createCase(array $attributes): string;

    public function caseCount(): int;

    public function case(string $approvalCasePublicId): ?HostTestRecord;

    public function resetContributions(): void;

    public function registerBinding(ApprovalFormBindingDescriptor $binding): void;

    public function registerDocumentSchema(ApprovalDocumentSchema $schema): void;

    /** @param list<int|string> $approverKeys */
    public function createFixedUserRoute(
        ?LegalEntity $legalEntity,
        string $key,
        string $name,
        array $approverKeys,
        Actor $author,
    ): ApprovalRouteReference;

    public function publishBusinessTemplate(
        ?LegalEntity $legalEntity,
        string $key,
        string $name,
        string $bindingKey,
        Actor $author,
    ): PublishedApprovalTemplate;

    /** @param array<string, mixed> $signature */
    public function approve(
        string $approvalCasePublicId,
        Actor $actor,
        string $idempotencyKey,
        array $signature,
    ): void;
}
