<?php

declare(strict_types=1);

namespace Nexia\Email\Contracts;

use Nexia\Email\EmailContext;
use Nexia\Email\EmailRecipient;
use Nexia\Email\EmailTemplate;

/** App owns business selection semantics; the host owns delivery and exclusions. */
interface EmailContribution
{
    public function appKey(): string;

    /** @return list<EmailTemplate> */
    public function templates(): array;

    /** Authorized sample subjects for authoring preview. @return list<array{label:string,ref:array<string,string>}> */
    public function previewSubjects(EmailContext $context, string $templateKey, string $search): array;

    /** @return array<string, array{label_key:string, requires_id:bool, include_children?:bool}> Selector kind metadata. */
    public function recipientKinds(): array;

    /** Search and resolve must enforce the same resource visibility and scope. @return list<array{id:string,label:string}> */
    public function search(EmailContext $context, string $kind, string $search): array;

    /** @param array<string, mixed> $selector @return list<EmailRecipient> */
    public function recipients(EmailContext $context, array $selector): array;

    /** Recheck current eligibility immediately before a queued message leaves the host. */
    public function mayDeliver(EmailContext $context, EmailRecipient $recipient): bool;

    /** Only declared fields from the authorized subject. @return array<string, string> */
    public function values(EmailContext $context, string $templateKey, int $version): array;
}
