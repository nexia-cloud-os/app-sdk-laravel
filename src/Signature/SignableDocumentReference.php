<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Immutable reference used only after a source checksum has been frozen. */
final readonly class SignableDocumentReference
{
    public function __construct(
        public string $publicId,
        public int $revision,
        public string $checksum,
    ) {
        if ($publicId === '' || $publicId !== trim($publicId) || $revision < 1
            || preg_match('/\A[0-9a-f]{64}\z/D', $checksum) !== 1) {
            throw new InvalidArgumentException('Signable document reference is invalid.');
        }
    }

    /** @return array{public_id: string, revision: int, checksum: string} */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'revision' => $this->revision,
            'checksum' => $this->checksum,
        ];
    }

    /** @param array{public_id: string, revision: int, checksum: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            publicId: $data['public_id'],
            revision: $data['revision'],
            checksum: $data['checksum'],
        );
    }
}
