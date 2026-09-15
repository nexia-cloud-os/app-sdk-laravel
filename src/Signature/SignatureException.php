<?php

declare(strict_types=1);

namespace Nexia\Signature;

use RuntimeException;

final class SignatureException extends RuntimeException
{
    /** @param array<string, scalar|null> $context */
    public function __construct(
        public readonly SignatureErrorCode $errorCode,
        string $message,
        public readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** @return array{error_code: string, message: string, context: array<string, scalar|null>} */
    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode->value,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }

    /** @param array{error_code: string, message: string, context?: array<string, scalar|null>} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            errorCode: SignatureErrorCode::from($data['error_code']),
            message: $data['message'],
            context: $data['context'] ?? [],
        );
    }
}
