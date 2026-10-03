<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database;

use InvalidArgumentException;
use SensitiveParameter;

/** One-time returned external integration credential, never persisted by an App. */
final readonly class ExternalDatabaseWriterCredential
{
    public function __construct(
        public string $host, public int $port, public string $database, public string $schema,
        public string $sslMode, public string $username, #[SensitiveParameter] public string $password,
    ) {
        if ($host === '' || $port < 1 || $port > 65535 || $database === '' || $schema === '' || $username === '' || $password === '') {
            throw new InvalidArgumentException('External database writer credential is invalid.');
        }
    }
}
