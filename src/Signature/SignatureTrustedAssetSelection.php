<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Selects one server-resolved trusted asset for one document-owned placement. */
final readonly class SignatureTrustedAssetSelection
{
    public function __construct(
        public string $kind,
        public string $publicId,
        public string $placementKey,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_]{0,79}\z/D', $kind) !== 1
            || preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $publicId) !== 1
            || preg_match('/\A[a-z][a-z0-9_.-]{0,119}\z/D', $placementKey) !== 1) {
            throw new InvalidArgumentException('Signature trusted asset selection is invalid.');
        }
    }

    /** @return array{kind: string, public_id: string, placement_key: string} */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'public_id' => $this->publicId,
            'placement_key' => $this->placementKey,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            kind: is_string($data['kind'] ?? null) ? $data['kind'] : '',
            publicId: is_string($data['public_id'] ?? null) ? $data['public_id'] : '',
            placementKey: is_string($data['placement_key'] ?? null) ? $data['placement_key'] : '',
        );
    }
}
