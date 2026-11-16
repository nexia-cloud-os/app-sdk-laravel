<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

/** Read-only App evidence for the Core-issued operator review work. */
interface WorkBoundImportReviewEvidence
{
    /**
     * Submit the same decision's App evidence; null means no durable work marker.
     * This does not authorize any App mutation or settle the review.
     *
     * @param  array{work_id:string,batch_id:string,legal_entity_public_id:string,source_checksum:string,owner_actor_public_id:string}|null  $evidence
     */
    public function submit(?array $evidence): void;
}
