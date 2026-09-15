<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Frozen authentication policy: an exact profile version and its required methods. */
final readonly class SignatureAuthenticationProfileSnapshot
{
    /**
     * @param  non-empty-list<SignatureAuthenticationMethod>  $requiredMethods
     */
    public function __construct(
        public string $profileKey,
        public string $version,
        public array $requiredMethods,
    ) {
        foreach (['profileKey' => $profileKey, 'version' => $version] as $name => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature authentication profile {$name} must be a normalized non-blank string.");
            }
        }

        if ($requiredMethods === []) {
            throw new InvalidArgumentException('Signature authentication profile must require at least one method.');
        }

        $values = array_map(static fn (SignatureAuthenticationMethod $method): string => $method->value, $requiredMethods);

        if (count($values) !== count(array_unique($values))) {
            throw new InvalidArgumentException('Signature authentication profile methods must be unique.');
        }
    }

    /** @return array{profile_key: string, version: string, required_methods: non-empty-list<string>} */
    public function toArray(): array
    {
        return [
            'profile_key' => $this->profileKey,
            'version' => $this->version,
            'required_methods' => array_map(
                static fn (SignatureAuthenticationMethod $method): string => $method->value,
                $this->requiredMethods,
            ),
        ];
    }

    /** @param array{profile_key: string, version: string, required_methods: non-empty-list<string>} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            profileKey: $data['profile_key'],
            version: $data['version'],
            requiredMethods: array_map(SignatureAuthenticationMethod::from(...), $data['required_methods']),
        );
    }

    /** Exact profile identity without changing the established snapshot serialization. */
    public function reference(): SignatureAuthenticationProfileReference
    {
        return new SignatureAuthenticationProfileReference(
            profileKey: $this->profileKey,
            version: $this->version,
        );
    }
}
