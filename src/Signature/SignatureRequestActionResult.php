<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Stable accepted-or-existing result for a cancel or resend request action. */
final readonly class SignatureRequestActionResult
{
    public function __construct(
        public string $requestPublicId,
        public SignatureRequestAction $action,
        public SignatureRequestActionStatus $actionStatus,
        public SignatureRequestStatus $requestStatus,
    ) {
        if ($requestPublicId === '' || $requestPublicId !== trim($requestPublicId)) {
            throw new InvalidArgumentException('Signature request action result public id must be normalized and non-blank.');
        }
    }

    /** @return array{signature_request_public_id: string, action: string, action_status: string, request_status: string} */
    public function toArray(): array
    {
        return [
            'signature_request_public_id' => $this->requestPublicId,
            'action' => $this->action->value,
            'action_status' => $this->actionStatus->value,
            'request_status' => $this->requestStatus->value,
        ];
    }

    /** @param array{signature_request_public_id: string, action: string, action_status: string, request_status: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            requestPublicId: $data['signature_request_public_id'],
            action: SignatureRequestAction::from($data['action']),
            actionStatus: SignatureRequestActionStatus::from($data['action_status']),
            requestStatus: SignatureRequestStatus::from($data['request_status']),
        );
    }
}
