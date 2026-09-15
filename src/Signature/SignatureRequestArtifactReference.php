<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * PII-free proof that Core still verifies a completed PDF and its evidence
 * manifest as the immutable artifacts for one request.
 */
final readonly class SignatureRequestArtifactReference
{
    public function __construct(
        public string $publicId,
        public string $completedChecksum,
        public string $manifestChecksum,
        public string $finalizedAt,
    ) {
        if (! self::uuid($publicId)
            || ! self::checksum($completedChecksum)
            || ! self::checksum($manifestChecksum)
            || ! self::timestamp($finalizedAt)) {
            throw new InvalidArgumentException('Signature request artifact reference is invalid.');
        }
    }

    /** @return array{public_id:string,completed_checksum:string,manifest_checksum:string,finalized_at:string} */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'completed_checksum' => $this->completedChecksum,
            'manifest_checksum' => $this->manifestChecksum,
            'finalized_at' => $this->finalizedAt,
        ];
    }

    /** @param array{public_id:string,completed_checksum:string,manifest_checksum:string,finalized_at:string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            publicId: $data['public_id'],
            completedChecksum: $data['completed_checksum'],
            manifestChecksum: $data['manifest_checksum'],
            finalizedAt: $data['finalized_at'],
        );
    }

    private static function uuid(string $value): bool
    {
        return preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $value) === 1;
    }

    private static function checksum(string $value): bool
    {
        return preg_match('/\A[0-9a-f]{64}\z/Di', $value) === 1;
    }

    private static function timestamp(string $value): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/D', $value) !== 1) {
            return false;
        }

        try {
            new DateTimeImmutable($value);
        } catch (\Exception) {
            return false;
        }

        return $value !== '';
    }
}
