<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/**
 * App-neutral instruction for a Process activity to wait for one immutable
 * SignatureRequest terminal outcome. It intentionally carries no recipient,
 * document bytes, or App model identity.
 */
final readonly class SignatureProcessWaitDescriptor
{
    /** @param non-empty-list<SignatureRequestOutcome> $allowedOutcomes */
    public function __construct(
        public string $requestPublicId,
        public array $allowedOutcomes,
    ) {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $requestPublicId) !== 1) {
            throw new InvalidArgumentException('Signature Process wait requestPublicId must be a UUID.');
        }
        if (! array_is_list($allowedOutcomes) || $allowedOutcomes === []) {
            throw new InvalidArgumentException('Signature Process waits require at least one allowed terminal outcome.');
        }

        $seen = [];
        foreach ($allowedOutcomes as $outcome) {
            if (! $outcome instanceof SignatureRequestOutcome || isset($seen[$outcome->value])) {
                throw new InvalidArgumentException('Signature Process wait outcomes must be unique terminal outcome values.');
            }
            $seen[$outcome->value] = true;
        }
    }

    /** @return array{signature_request_public_id:string,allowed_terminal_outcomes:list<string>} */
    public function toArray(): array
    {
        return [
            'signature_request_public_id' => $this->requestPublicId,
            'allowed_terminal_outcomes' => array_map(
                static fn (SignatureRequestOutcome $outcome): string => $outcome->value,
                $this->allowedOutcomes,
            ),
        ];
    }

    /** @param array{signature_request_public_id:string,allowed_terminal_outcomes:list<string>} $data */
    public static function fromArray(array $data): self
    {
        $requestPublicId = $data['signature_request_public_id'] ?? null;
        $outcomes = $data['allowed_terminal_outcomes'] ?? null;
        if (! is_string($requestPublicId)
            || ! is_array($outcomes)
            || ! array_is_list($outcomes)
            || array_filter($outcomes, static fn (mixed $outcome): bool => ! is_string($outcome)) !== []) {
            throw new InvalidArgumentException('Signature Process wait serialization is invalid.');
        }

        try {
            $allowedOutcomes = array_map(SignatureRequestOutcome::from(...), $outcomes);
        } catch (\ValueError) {
            throw new InvalidArgumentException('Signature Process wait serialization contains an invalid terminal outcome.');
        }

        return new self(
            requestPublicId: $requestPublicId,
            allowedOutcomes: $allowedOutcomes,
        );
    }
}
