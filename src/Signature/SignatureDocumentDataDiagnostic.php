<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Value-free bounded diagnostic retained only for authorized host observability. */
final readonly class SignatureDocumentDataDiagnostic
{
    public function __construct(
        public SignatureDocumentDataDiagnosticCode $code,
        public ?int $retryAfterSeconds = null,
    ) {
        if ($retryAfterSeconds !== null && ($retryAfterSeconds < 1 || $retryAfterSeconds > 3600)) {
            throw new InvalidArgumentException('Signature document data diagnostic retry_after_seconds must be between 1 and 3600.');
        }
    }

    /** @return array{code: string, retry_after_seconds: int|null} */
    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'retry_after_seconds' => $this->retryAfterSeconds,
        ];
    }
}
