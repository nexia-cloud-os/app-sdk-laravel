<?php

declare(strict_types=1);

namespace Nexia\Integrations;

use InvalidArgumentException;

/** App-owned defaults for a human-reviewed external HTTP connection. No secrets or grants. */
final readonly class ExternalApiDefinition
{
    /** @param list<array{name: string, method: string, path: string, input_schema: array, enabled: bool, agent_enabled: bool}> $operations */
    public function __construct(
        public string $key,
        public string $name,
        public string $purpose,
        public string $scope,
        public ?string $endpointUrl,
        public array $operations,
        public bool $enabled = false,
        public ?array $receiver = null,
        public ?array $authentication = null,
    ) {}

    public function toArray(): array
    {
        return ['key' => $this->key, 'name' => $this->name, 'purpose' => $this->purpose,
            'scope' => $this->scope, 'endpoint_url' => $this->endpointUrl, 'enabled' => $this->enabled,
            'operations' => $this->operations, 'receiver' => $this->receiver,
            'authentication' => $this->authentication];
    }

    /** @param list<array<string, mixed>> $definitions */
    public static function validateCatalog(array $definitions, string $appKey): void
    {
        if (! array_is_list($definitions) || count($definitions) > 100) {
            throw new InvalidArgumentException('Invalid external API catalog.');
        }
        $seen = [];
        foreach ($definitions as $definition) {
            if (! is_array($definition) || array_diff(['key', 'name', 'purpose', 'scope', 'endpoint_url', 'enabled', 'operations', 'receiver'], array_keys($definition)) !== []
                || array_diff(array_keys($definition), ['key', 'name', 'purpose', 'scope', 'endpoint_url', 'enabled', 'operations', 'receiver', 'authentication']) !== []
                || ! is_string($definition['key'] ?? null) || strlen($definition['key']) > 191
                || ! preg_match('/\A'.preg_quote($appKey, '/').'\.[a-z][a-z0-9_.-]*\z/D', $definition['key'])
                || isset($seen[$definition['key']]) || ! is_string($definition['name'] ?? null) || trim($definition['name']) === '' || strlen($definition['name']) > 160
                || ! is_string($definition['purpose'] ?? null) || trim($definition['purpose']) === '' || strlen($definition['purpose']) > 2000
                || ! in_array($definition['scope'] ?? null, ['tenant', 'legal_entity'], true)
                || (($definition['endpoint_url'] ?? null) !== null && (! is_string($definition['endpoint_url'])
                    || strlen($definition['endpoint_url']) > 4096 || ! filter_var($definition['endpoint_url'], FILTER_VALIDATE_URL)
                    || parse_url($definition['endpoint_url'], PHP_URL_SCHEME) !== 'https'
                    || parse_url($definition['endpoint_url'], PHP_URL_QUERY) !== null
                    || parse_url($definition['endpoint_url'], PHP_URL_FRAGMENT) !== null
                    || parse_url($definition['endpoint_url'], PHP_URL_USER) !== null
                    || parse_url($definition['endpoint_url'], PHP_URL_PASS) !== null))
                || ! is_bool($definition['enabled'] ?? null)
                || ! is_array($definition['operations'] ?? null) || ! array_is_list($definition['operations']) || count($definition['operations']) > 100
                || ($definition['operations'] === [] && ($definition['receiver'] ?? null) === null)
                || ($definition['operations'] !== [] && ($definition['endpoint_url'] ?? null) === null)
                || (($definition['authentication'] ?? null) !== null && (! is_array($definition['authentication'])
                    || array_diff(array_keys($definition['authentication']), ['type', 'header']) !== []
                    || ! in_array($definition['authentication']['type'] ?? null, ['bearer', 'api_key', 'webhook_hmac'], true)
                    || (($definition['authentication']['type'] ?? null) === 'api_key'
                        ? ! in_array($definition['authentication']['header'] ?? null, ['x-api-key', 'api-key', 'private-token'], true)
                        : array_key_exists('header', $definition['authentication']))
                    || $definition['operations'] === []))
                || ($definition['receiver'] ?? null) !== null && ($definition['scope'] !== 'legal_entity' || ! is_array($definition['receiver'])
                    || array_diff(array_keys($definition['receiver']), ['mode', 'expires_days', 'input_schema']) !== []
                    || ! in_array($definition['receiver']['mode'] ?? null, ['api', 'webhook'], true)
                    || ! is_int($definition['receiver']['expires_days'] ?? null) || $definition['receiver']['expires_days'] < 1 || $definition['receiver']['expires_days'] > 365
                    || ! is_array($definition['receiver']['input_schema'] ?? null))) {
                throw new InvalidArgumentException('Invalid external API definition.');
            }
            $seen[$definition['key']] = true;
            $operations = [];
            foreach ($definition['operations'] as $operation) {
                if (! is_array($operation) || array_diff(array_keys($operation), ['name', 'method', 'path', 'input_schema', 'enabled', 'agent_enabled']) !== []
                    || ! is_string($operation['name'] ?? null) || trim($operation['name']) === '' || strlen($operation['name']) > 160
                    || ! in_array($operation['method'] ?? null, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
                    || ! is_string($operation['path'] ?? null) || ! preg_match('~\A/(?!/)[A-Za-z0-9/_\-.]*\z~D', $operation['path'])
                    || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $operation['path'])
                    || ! is_array($operation['input_schema'] ?? null) || ! is_bool($operation['enabled'] ?? null)
                    || ! is_bool($operation['agent_enabled'] ?? null) || isset($operations[$operation['name']])) {
                    throw new InvalidArgumentException('Invalid external API operation.');
                }
                $operations[$operation['name']] = true;
            }
        }
    }
}
