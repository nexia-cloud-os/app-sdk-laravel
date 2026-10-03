<?php

declare(strict_types=1);

namespace Nexia\Laravel\Database\Contracts;

use Nexia\Laravel\Database\ExternalDatabaseWriterCredential;
use Nexia\Laravel\Database\ExternalDatabaseWriterDefinition;
use Nexia\Laravel\Database\ExternalDatabaseWriterProvisioning;

/** Core controls DDL and credentials; Apps only describe an owned insert target. */
interface ExternalDatabaseWriterProvisioner
{
    public function request(ExternalDatabaseWriterDefinition $definition, bool $rotate = false): ExternalDatabaseWriterProvisioning;

    public function status(string $handle): ExternalDatabaseWriterProvisioning;

    public function reveal(string $handle): ExternalDatabaseWriterCredential;
}
