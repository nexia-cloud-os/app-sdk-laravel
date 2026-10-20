<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/** Protected callback: reserve before execution, accepted after commit, release after definitive abandonment. */
final readonly class SignatureBulkBindingLifecycleContext
{
    public Actor $actor;
    public LegalEntity $legalEntity;

    /** @param list<SignatureParticipantSnapshot> $participants */
    public function __construct(
        public SignatureBulkBindingExecutionContext $execution,
        public string $operationId,
        public string $phase,
        public ?SignableDocumentReference $document = null,
        public ?int $sourcePageCount = null,
        public ?string $requestPublicId = null,
        public array $participants = [],
        public ?SignatureConsentPolicyReference $consent = null,
        public ?SignatureExpiryPolicy $expiry = null,
    ) {
        $this->actor = $execution->actor;
        $this->legalEntity = $execution->legalEntity;
        if ($operationId === '' || strlen($operationId) > 191 || trim($operationId) !== $operationId
            || preg_match('/[\x00-\x1f\x7f]/', $operationId)
            || !in_array($phase, ['reserve', 'accepted', 'release'], true)) {
            throw new InvalidArgumentException('Invalid signature bulk lifecycle identity.');
        }
        if ($phase === 'accepted') {
            if ($document === null || $sourcePageCount === null || $sourcePageCount < 1
                || !is_string($requestPublicId) || !preg_match('/\A[0-9a-f-]{36}\z/D', $requestPublicId)
                || $participants === [] || !array_is_list($participants) || $consent === null || $expiry === null) {
                throw new InvalidArgumentException('Incomplete signature bulk acceptance receipt.');
            }
            foreach ($participants as $participant) {
                if (!$participant instanceof SignatureParticipantSnapshot) throw new InvalidArgumentException('Invalid receipt participant.');
            }
        } elseif ($document !== null || $sourcePageCount !== null || $requestPublicId !== null || $participants !== [] || $consent !== null || $expiry !== null) {
            throw new InvalidArgumentException('Unexpected signature bulk receipt.');
        }
    }

    public function __debugInfo(): array
    {
        return ['operation_id' => $this->operationId, 'phase' => $this->phase];
    }
}
