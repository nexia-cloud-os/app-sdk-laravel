<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Exact version of the host-managed authentication profile selected for a signer. */
final readonly class SignatureAuthenticationProfileReference
{
    public function __construct(
        public string $profileKey,
        public string $version,
    ) {
        foreach (['profileKey' => $profileKey, 'version' => $version] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature authentication profile {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($profileKey) > 160 || strlen($version) > 32) {
            throw new InvalidArgumentException('Signature authentication profile reference contains an oversized identity.');
        }
    }

    /** @return array{profile_key: string, version: string} */
    public function toArray(): array
    {
        return [
            'profile_key' => $this->profileKey,
            'version' => $this->version,
        ];
    }

    /** @param array{profile_key: string, version: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            profileKey: $data['profile_key'],
            version: $data['version'],
        );
    }
}
