<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/**
 * Opaque, hash-only request-password material issued by the Signature host.
 * It deliberately has no array or JSON representation.
 */
final readonly class SignatureRequestPasswordVerifier
{
    private function __construct(private string $handoffValue) {}

    /** Rehydrates a previously encrypted App handoff value. */
    public static function fromHandoffValue(string $handoffValue): self
    {
        if ($handoffValue === '' || strlen($handoffValue) > 1024) {
            throw new InvalidArgumentException('Request password verifier material is invalid.');
        }

        return new self($handoffValue);
    }

    /** For encrypted, durable App handoff storage only. */
    public function handoffValue(): string
    {
        return $this->handoffValue;
    }

    /** For the SDK handoff to the Core Signature host only, never a browser or log payload. */
    public function valueForSignatureHost(): string
    {
        return $this->handoffValue;
    }
}
