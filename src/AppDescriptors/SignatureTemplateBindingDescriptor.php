<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\AppDescriptors\Contracts\AppDescriptor;
use InvalidArgumentException;

/** Schema for one reusable host E-Signature template family. */
final readonly class SignatureTemplateBindingDescriptor implements AppDescriptor
{
    /** Canonical descriptor key used by generic App contribution validators. */
    public string $key;

    /**
     * @param  list<SignatureTemplateVariableDescriptor>  $variables
     * @param  non-empty-list<SignatureSignatoryRoleDescriptor>  $signatoryRoles
     * @param  array<string, mixed>  $syntheticSample
     * @param  non-empty-list<string>  $templateKeys
     */
    public function __construct(
        public string $appKey,
        public string $bindingKey,
        public string $resourceKey,
        public string $labelKey,
        public string $descriptionKey,
        public string $createPermissionKey,
        public array $variables,
        public array $signatoryRoles,
        public array $syntheticSample,
        public array $templateKeys,
        public string $version = '1.0',
        public DescriptorStatus $status = DescriptorStatus::Active,
        public ?string $requestNavigationId = null,
        public ?string $requestNavigationRoute = null,
    ) {
        $this->key = $bindingKey;

        foreach ([
            'app_key' => $appKey,
            'binding_key' => $bindingKey,
            'resource_key' => $resourceKey,
            'label_key' => $labelKey,
            'description_key' => $descriptionKey,
            'create_permission_key' => $createPermissionKey,
            'version' => $version,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature template binding {$field} must be normalized and non-blank.");
            }
        }

        // The binding family and its create authority belong to the declaring
        // App. Its resource may be a shared Core reference or another owner
        // resolved through the Resource Reference contract.
        foreach ([$bindingKey, $createPermissionKey] as $ownedKey) {
            if (! str_starts_with($ownedKey, $appKey.'.')) {
                throw new InvalidArgumentException("Signature template binding key [{$ownedKey}] must be owned by app [{$appKey}].");
            }
        }

        if ($signatoryRoles === [] || $templateKeys === []) {
            throw new InvalidArgumentException("Signature template binding [{$bindingKey}] requires signatory roles and template keys.");
        }

        $variableMap = $this->indexUnique($variables, SignatureTemplateVariableDescriptor::class, 'variable');
        $this->indexUnique($signatoryRoles, SignatureSignatoryRoleDescriptor::class, 'signatory role');

        foreach ($templateKeys as $templateKey) {
            if (! is_string($templateKey) || ! str_starts_with($templateKey, $appKey.'.')) {
                throw new InvalidArgumentException("Signature template binding [{$bindingKey}] template keys must be owned by app [{$appKey}].");
            }
        }

        if ($requestNavigationId !== null
            && ($requestNavigationId === '' || $requestNavigationId !== trim($requestNavigationId))) {
            throw new InvalidArgumentException('Signature template binding request_navigation_id must be normalized and non-blank when provided.');
        }

        if ($requestNavigationRoute !== null) {
            if ($requestNavigationId === null) {
                throw new InvalidArgumentException('Signature template binding request_navigation_route requires request_navigation_id.');
            }
            if ($requestNavigationRoute === ''
                || $requestNavigationRoute !== trim($requestNavigationRoute)
                || ! str_starts_with($requestNavigationRoute, '/')
                || str_starts_with($requestNavigationRoute, '//')
                || preg_match('/[\x00-\x20\x7F]/', $requestNavigationRoute) === 1) {
                throw new InvalidArgumentException('Signature template binding request_navigation_route must be a normalized local absolute route.');
            }
        }

        if (count($templateKeys) !== count(array_unique($templateKeys))) {
            throw new InvalidArgumentException("Signature template binding [{$bindingKey}] template keys must be unique.");
        }

        foreach ($syntheticSample as $key => $value) {
            if (! isset($variableMap[$key])) {
                throw new InvalidArgumentException("Signature template binding [{$bindingKey}] synthetic sample contains unknown variable [{$key}].");
            }
            if (! $variableMap[$key]->accepts($value)) {
                throw new InvalidArgumentException("Signature template binding [{$bindingKey}] synthetic sample variable [{$key}] does not match its declared type.");
            }
        }

        foreach ($variableMap as $variable) {
            if ($variable->required && ! array_key_exists($variable->key, $syntheticSample)) {
                throw new InvalidArgumentException("Signature template binding [{$bindingKey}] synthetic sample is missing required variable [{$variable->key}].");
            }
        }
    }

    /**
     * @template T of object
     *
     * @param  array<int, mixed>  $entries
     * @param  class-string<T>  $expectedClass
     * @return array<string, T>
     */
    private function indexUnique(array $entries, string $expectedClass, string $label): array
    {
        $indexed = [];

        foreach ($entries as $entry) {
            if (! $entry instanceof $expectedClass) {
                throw new InvalidArgumentException("Signature template binding [{$this->bindingKey}] {$label} entries must be {$expectedClass} instances.");
            }
            if (isset($indexed[$entry->key])) {
                throw new InvalidArgumentException("Signature template binding [{$this->bindingKey}] contains duplicate {$label} [{$entry->key}].");
            }
            $indexed[$entry->key] = $entry;
        }

        return $indexed;
    }

    /**
     * @return array{
     *   app_key: string,
     *   binding_key: string,
     *   resource_key: string,
     *   label_key: string,
     *   description_key: string,
     *   create_permission_key: string,
     *   variables: list<array{key: string, label_key: string, type: string, required: bool, sensitivity: string, formatter: string|null}>,
     *   signatory_roles: list<array<string, mixed>>,
     *   synthetic_sample: array<string, mixed>,
     *   template_keys: list<string>,
     *   version: string,
     *   status: string,
     *   request_navigation_id: string|null,
     *   request_navigation_route: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'app_key' => $this->appKey,
            'binding_key' => $this->bindingKey,
            'resource_key' => $this->resourceKey,
            'label_key' => $this->labelKey,
            'description_key' => $this->descriptionKey,
            'create_permission_key' => $this->createPermissionKey,
            'variables' => array_map(
                static fn (SignatureTemplateVariableDescriptor $variable): array => $variable->toArray(),
                $this->variables,
            ),
            'signatory_roles' => array_map(
                static fn (SignatureSignatoryRoleDescriptor $role): array => $role->toArray(),
                $this->signatoryRoles,
            ),
            'synthetic_sample' => $this->syntheticSample,
            'template_keys' => $this->templateKeys,
            'version' => $this->version,
            'status' => $this->status->value,
            'request_navigation_id' => $this->requestNavigationId,
            'request_navigation_route' => $this->requestNavigationRoute,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            appKey: $data['app_key'],
            bindingKey: $data['binding_key'],
            resourceKey: $data['resource_key'],
            labelKey: $data['label_key'],
            descriptionKey: $data['description_key'],
            createPermissionKey: $data['create_permission_key'],
            variables: array_map(SignatureTemplateVariableDescriptor::fromArray(...), $data['variables']),
            signatoryRoles: array_map(SignatureSignatoryRoleDescriptor::fromArray(...), $data['signatory_roles']),
            syntheticSample: $data['synthetic_sample'],
            templateKeys: $data['template_keys'],
            version: $data['version'],
            status: DescriptorStatus::from($data['status']),
            requestNavigationId: isset($data['request_navigation_id'])
                ? (string) $data['request_navigation_id']
                : null,
            requestNavigationRoute: isset($data['request_navigation_route'])
                ? (string) $data['request_navigation_route']
                : null,
        );
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }

    public function acceptsTemplateKey(string $templateKey): bool
    {
        foreach ($this->templateKeys as $allowed) {
            if (str_ends_with($allowed, '.*')) {
                $prefix = substr($allowed, 0, -1);
                if (str_starts_with($templateKey, $prefix)
                    && strlen($templateKey) > strlen($prefix)) {
                    return true;
                }

                continue;
            }
            if (hash_equals($allowed, $templateKey)) {
                return true;
            }
        }

        return false;
    }
}
