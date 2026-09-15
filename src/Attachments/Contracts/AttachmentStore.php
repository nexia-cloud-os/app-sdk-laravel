<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Nexia\Attachments\AttachmentTarget;
use Nexia\Attachments\GeneratedFile;
use Nexia\Attachments\StoredAttachment;
use Nexia\Attachments\StoredFile;
use Nexia\Identity\Contracts\Actor;

/** Host persistence boundary for generated files and their resource attachments. */
interface AttachmentStore
{
    public function createGeneratedFile(GeneratedFile $file): StoredFile;

    public function attach(StoredFile $file, AttachmentTarget $target, Actor $actor): StoredAttachment;

    /** Whether a specific attachment belongs to a target. */
    public function exists(string $attachmentPublicId, AttachmentTarget $target): bool;

    /** Whether a target owns at least one media evidence item. */
    public function hasAnyForTarget(AttachmentTarget $target): bool;

    /**
     * Whether a target already holds a given file.
     *
     * Distinct from `exists` because it is keyed by the *file*: a caller holding
     * an upload wants to know whether attaching it again would duplicate, and it
     * has no attachment id to ask with. Both identifiers are called a public id
     * and mean different things, so the difference lives in the name.
     */
    public function fileAttached(string $filePublicId, AttachmentTarget $target): bool;

    public function fileForAttachment(string $attachmentPublicId): ?StoredFile;

    public function downloadHrefIfAuthorized(string $attachmentPublicId, Actor $actor): ?string;
}
