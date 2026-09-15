<?php

declare(strict_types=1);

namespace Nexia\Attachments\Contracts;

use Closure;
use Nexia\Attachments\FileDelivery;

/** Durable server-side delivery attempts; client receipt remains unknowable. */
interface FileDeliveryAttempts
{
    public function prepare(FileDelivery $delivery): string;

    public function started(string $attemptPublicId): void;

    public function succeeded(string $attemptPublicId): void;

    public function failed(string $attemptPublicId, string $reasonCode): void;

    /** @param Closure():void $send @return Closure():void */
    public function callback(string $attemptPublicId, Closure $send): Closure;
}
