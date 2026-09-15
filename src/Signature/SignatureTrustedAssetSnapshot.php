<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Non-secret provenance for a trusted asset embedded by the host renderer. */
final readonly class SignatureTrustedAssetSnapshot
{
    public function __construct(
        public string $kind,
        public string $publicId,
        public int $version,
        public string $sourceChecksum,
        public string $usagePublicId,
        public string $outputChecksum,
        public string $placementKey,
    ) {
        $uuid = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di';
        if (preg_match('/\A[a-z][a-z0-9_]{0,79}\z/D', $kind) !== 1
            || preg_match($uuid, $publicId) !== 1
            || preg_match($uuid, $usagePublicId) !== 1
            || preg_match('/\A[a-z][a-z0-9_.-]{0,119}\z/D', $placementKey) !== 1
            || $version < 1
            || preg_match('/\A[0-9a-f]{64}\z/D', $sourceChecksum) !== 1
            || preg_match('/\A[0-9a-f]{64}\z/D', $outputChecksum) !== 1) {
            throw new InvalidArgumentException('Signature trusted asset provenance is invalid.');
        }
    }

    /** @return array{kind: string, public_id: string, version: int, source_checksum: string, usage_public_id: string, output_checksum: string, placement_key: string} */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'public_id' => $this->publicId,
            'version' => $this->version,
            'source_checksum' => $this->sourceChecksum,
            'usage_public_id' => $this->usagePublicId,
            'output_checksum' => $this->outputChecksum,
            'placement_key' => $this->placementKey,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            kind: is_string($data['kind'] ?? null) ? $data['kind'] : '',
            publicId: is_string($data['public_id'] ?? null) ? $data['public_id'] : '',
            version: is_int($data['version'] ?? null) ? $data['version'] : 0,
            sourceChecksum: is_string($data['source_checksum'] ?? null) ? $data['source_checksum'] : '',
            usagePublicId: is_string($data['usage_public_id'] ?? null) ? $data['usage_public_id'] : '',
            outputChecksum: is_string($data['output_checksum'] ?? null) ? $data['output_checksum'] : '',
            placementKey: is_string($data['placement_key'] ?? null) ? $data['placement_key'] : '',
        );
    }
}
