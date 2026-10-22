<?php

declare(strict_types=1);

use Nexia\Integrations\ExternalApiDefinition;

require dirname(__DIR__).'/vendor/autoload.php';

$definition = new ExternalApiDefinition('sample.shipping', 'Shipping', 'Book shipments', 'legal_entity',
    'https://shipping.example.com', [['name' => 'Book', 'method' => 'POST', 'path' => '/shipments',
        'input_schema' => ['type' => 'object'], 'enabled' => true, 'agent_enabled' => false]],
    receiver: ['mode' => 'webhook', 'expires_days' => 30, 'input_schema' => ['type' => 'object']]);
ExternalApiDefinition::validateCatalog([$definition->toArray()], 'sample');
$receiver = new ExternalApiDefinition('sample.inbound', 'Inbound', 'Receive shipment status', 'legal_entity',
    null, [], receiver: ['mode' => 'webhook', 'expires_days' => 30, 'input_schema' => ['type' => 'object']]);
ExternalApiDefinition::validateCatalog([$receiver->toArray()], 'sample');
try {
    ExternalApiDefinition::validateCatalog([array_diff_key($receiver->toArray(), ['endpoint_url' => true])], 'sample');
    throw new RuntimeException('Incomplete external API declaration was accepted.');
} catch (InvalidArgumentException) {
    // Expected trust-boundary rejection.
}

foreach ([
    ['key' => 'other.shipping'],
    ['scope' => 'personal'],
    ['receiver' => ['mode' => 'webhook', 'expires_days' => 366, 'input_schema' => ['type' => 'object']]],
    ['operations' => [['name' => 'Book', 'method' => 'POST', 'path' => '/../admin',
        'input_schema' => ['type' => 'object'], 'enabled' => true, 'agent_enabled' => false]]],
    ['secret' => 'must-not-be-accepted'],
    ['endpoint_url' => null],
    ['endpoint_url' => 'https://example.com?token=secret'],
    ['endpoint_url' => 'https://example.com/#private'],
    ['authentication' => ['type' => 'api_key', 'header' => 'authorization']],
    ['authentication' => ['type' => 'bearer', 'secret' => 'must-not-be-accepted']],
] as $change) {
    try {
        ExternalApiDefinition::validateCatalog([[...$definition->toArray(), ...$change]], 'sample');
        throw new RuntimeException('Invalid external API declaration was accepted.');
    } catch (InvalidArgumentException) {
        // Expected trust-boundary rejection.
    }
}

try {
    ExternalApiDefinition::validateCatalog([(new ExternalApiDefinition('sample.empty', 'Empty', 'No route',
        'legal_entity', null, []))->toArray()], 'sample');
    throw new RuntimeException('Empty external API declaration was accepted.');
} catch (InvalidArgumentException) {
    // Expected trust-boundary rejection.
}
