<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Exact version of the host-managed consent text selected for a request. */
final readonly class SignatureConsentPolicyReference
{
    public function __construct(
        public string $policyKey,
        public string $version,
    ) {
        foreach (['policyKey' => $policyKey, 'version' => $version] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature consent policy {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($policyKey) > 160 || strlen($version) > 32) {
            throw new InvalidArgumentException('Signature consent policy reference contains an oversized identity.');
        }
    }

    /** @return array{policy_key: string, version: string} */
    public function toArray(): array
    {
        return [
            'policy_key' => $this->policyKey,
            'version' => $this->version,
        ];
    }

    /** @param array{policy_key: string, version: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            policyKey: $data['policy_key'],
            version: $data['version'],
        );
    }
}
