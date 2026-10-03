<?php

declare(strict_types=1);

use Nexia\Laravel\Database\ExternalDatabaseWriterCredential;
use Nexia\Laravel\Database\ExternalDatabaseWriterDefinition;
use Nexia\Laravel\Database\ExternalDatabaseWriterProvisioning;

require __DIR__.'/register-source-autoload.php';

function externalDatabaseWriterContractMustFail(callable $callback): void
{
    try {
        $callback();
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException('Expected an external database writer contract validation failure.');
}

$definition = new ExternalDatabaseWriterDefinition(
    's1_events',
    'secom_link_events',
    ['event_datetime', 'terminal_id', 'raw_payload'],
    'time-absence.s1_integration.activate',
    'time-absence.s1_integration.read',
);
assert($definition->toArray() === [
    'key' => 's1_events', 'table' => 'secom_link_events',
    'insert_columns' => ['event_datetime', 'terminal_id', 'raw_payload'],
    'permission' => 'time-absence.s1_integration.activate',
    'read_permission' => 'time-absence.s1_integration.read',
]);
assert((new ExternalDatabaseWriterProvisioning('0f2f7c8e-2f76-4d51-9f8e-b9f8cf491b1f', 'pending'))->connection === null);
assert((new ExternalDatabaseWriterCredential('db.example.test', 5432, 'tenant', 'app_tenant', 'require', 'writer', 'secret'))->username === 'writer');

externalDatabaseWriterContractMustFail(fn () => new ExternalDatabaseWriterDefinition(
    'bad-key', 'secom_link_events', ['id'], 'time-absence.s1_integration.activate', 'time-absence.s1_integration.read',
));
externalDatabaseWriterContractMustFail(fn () => new ExternalDatabaseWriterDefinition(
    's1_events', 'secom_link_events', ['id', 'id'], 'time-absence.s1_integration.activate', 'time-absence.s1_integration.read',
));
externalDatabaseWriterContractMustFail(fn () => new ExternalDatabaseWriterCredential('', 5432, 'tenant', 'app_tenant', 'require', 'writer', 'secret'));

echo "External database writer contracts passed.\n";
