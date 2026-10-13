<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database\Contracts;

/** Core owns the writer's source-IP policy and asynchronous network reconciliation. */
interface ExternalDatabaseWriterNetworkAccess
{
    /** @return array{enabled:bool,addresses:list<string>,revision:int,applied:bool,observed_ip:?string} */
    public function network(string $handle): array;

    /**
     * Replace this writer's public IPv4 addresses; an empty list revokes all access.
     * @param list<string> $addresses
     * @return array{enabled:bool,addresses:list<string>,revision:int,applied:bool,observed_ip:?string}
     */
    public function setNetwork(string $handle, array $addresses, int $expectedRevision): array;
}
