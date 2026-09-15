<?php

declare(strict_types=1);

namespace Nexia\ResourceComposition;

use InvalidArgumentException;

final class CompositionSpecValidationException extends InvalidArgumentException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $path,
        string $message,
    ) {
        parent::__construct($message);
    }

    /** @return array{error_code: string, path: string, message: string} */
    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'path' => $this->path,
            'message' => $this->getMessage(),
        ];
    }
}
