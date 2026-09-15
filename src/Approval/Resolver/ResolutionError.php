<?php

declare(strict_types=1);

namespace Nexia\Approval\Resolver;

final readonly class ResolutionError
{
    /** @param array<string, int|string|bool> $params */
    public function __construct(
        public ResolutionErrorCode $code,
        public string $message,
        public ?int $userId = null,
        public ?string $reason = null,
        public array $params = [],
    ) {}

    public function category(): string
    {
        return $this->code->category();
    }
}
