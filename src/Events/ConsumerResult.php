<?php

declare(strict_types=1);

namespace Nexia\Events;

use Throwable;

final class ConsumerResult
{
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD = 'dead';

    private function __construct(
        public readonly string $status,
        public readonly bool $handlerRan,
        public readonly ?Throwable $error = null,
        /** A delivery was deliberately prevented from executing the handler. */
        public readonly bool $suppressed = false,
        /** Durable per-message count after this suppressed delivery. */
        public readonly ?int $suppressionCount = null,
    ) {}

    public static function processed(): self
    {
        return new self(self::STATUS_PROCESSED, true);
    }

    public static function alreadyProcessed(?int $suppressionCount = null): self
    {
        return new self(self::STATUS_PROCESSED, false, suppressed: true, suppressionCount: $suppressionCount);
    }

    public static function failed(string $status, Throwable $error): self
    {
        return new self($status, true, $error);
    }

    public static function skippedDead(?int $suppressionCount = null): self
    {
        return new self(self::STATUS_DEAD, false, suppressed: true, suppressionCount: $suppressionCount);
    }

    public static function inProgress(?int $suppressionCount = null): self
    {
        return new self(self::STATUS_PROCESSING, false, suppressed: true, suppressionCount: $suppressionCount);
    }

    public function wasProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function handlerExecuted(): bool
    {
        return $this->handlerRan;
    }

    /** True when this delivery was a durable Inbox no-op. */
    public function wasSuppressed(): bool
    {
        return $this->suppressed;
    }

    public function isFailure(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_DEAD], true);
    }

    public function isDead(): bool
    {
        return $this->status === self::STATUS_DEAD;
    }
}
