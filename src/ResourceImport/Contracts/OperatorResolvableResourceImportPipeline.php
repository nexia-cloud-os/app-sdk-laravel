<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Nexia\Identity\Contracts\Actor;

/** Optional App-owned terminal evidence for an uncertain, work-bound import. */
interface OperatorResolvableResourceImportPipeline
{
    /**
     * Read only. The host supplies the original work identity and the authorized
     * operator; return null if no durable work-to-batch binding exists.
     *
     * @return array{work_id:string,batch_id:string,legal_entity_public_id:string,source_checksum:string,owner_actor_public_id:string}|null
     */
    public function inspectForResolution(string $originalWorkId, Actor $operator): ?array;

    /**
     * Revalidate the same work, batch, entity, source checksum and original owner
     * under the App's mutation lock. Atomically retain existing effects, mark only
     * that batch closed without claiming success, and store an immutable receipt.
     * Repeating the same decision returns that receipt; changed identity or digest
     * must be rejected. The host must first compare the read-only evidence against
     * its own retained source and fence the original worker.
     *
     * @param  array{batch_id:string,legal_entity_public_id:string,source_checksum:string,owner_actor_public_id:string}  $expectedIdentity
     * @return array{work_id:string,decision_fingerprint:string,evidence_fingerprint:string,batch_id:string,legal_entity_public_id:string,source_checksum:string,owner_actor_public_id:string,outcome:'closed_unconfirmed'}
     */
    public function closeForResolution(string $originalWorkId, string $decisionFingerprint, string $evidenceFingerprint, array $expectedIdentity, Actor $operator): array;
}
