<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Form;

use RuntimeException;

final class FormSchemaException extends RuntimeException
{
    public ?string $errorCode = null;

    public static function emptyBody(): self
    {
        $exception = new self('An approval form document requires non-empty content.');
        $exception->errorCode = 'form_content_required';

        return $exception;
    }

    /**
     * @return array{code: string|null, params: array<string, mixed>}
     */
    public function toErrorPayload(): array
    {
        return [
            'code' => $this->errorCode,
            'params' => [],
        ];
    }
}
