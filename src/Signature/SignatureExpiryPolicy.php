<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;

/** Immutable request deadline and the app-neutral outcome required at that deadline. */
final readonly class SignatureExpiryPolicy
{
    public function __construct(
        public string $expiresAt,
        public SignatureExpiryAction $onExpiry = SignatureExpiryAction::Expire,
    ) {
        if (! self::isIsoTimestamp($expiresAt)) {
            throw new InvalidArgumentException('Signature request expiry must be an ISO-8601 timestamp with an explicit timezone.');
        }
    }

    /** @return array{expires_at: string, on_expiry: string} */
    public function toArray(): array
    {
        return [
            'expires_at' => $this->expiresAt,
            'on_expiry' => $this->onExpiry->value,
        ];
    }

    /** @param array{expires_at: string, on_expiry: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            expiresAt: $data['expires_at'],
            onExpiry: SignatureExpiryAction::from($data['on_expiry']),
        );
    }

    private static function isIsoTimestamp(string $value): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $value) !== 1) {
            return false;
        }

        try {
            new DateTimeImmutable($value);
        } catch (\Exception) {
            return false;
        }

        return true;
    }
}
