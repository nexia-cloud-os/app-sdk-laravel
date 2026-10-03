<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database;

use InvalidArgumentException;

/** Opaque Core-owned writer request. No role name or password belongs to App state. */
final readonly class ExternalDatabaseWriterProvisioning
{
    /** @param array{host:string,port:int,database:string,schema:string,ssl_mode:string,username:string}|null $connection */
    public function __construct(public string $handle, public string $status, public ?array $connection = null)
    {
        if (! preg_match('/\A[0-9a-f-]{36}\z/D', $handle) || ! in_array($status, ['pending', 'provisioning', 'ready', 'needs_review'], true)) {
            throw new InvalidArgumentException('External database writer provisioning is invalid.');
        }
    }
}
