<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;
use Nexia\ResourceReference\ResourceRef;
use Nexia\Signature\SignatureAuthenticationMethod;
use Nexia\Signature\SignatureBulkParticipantAssignmentSource;
use Nexia\Signature\SignatureInvitationChannel;

/** Public, PII-free Group Bulk capability metadata for one exact binding. */
final readonly class SignatureBulkBindingDescriptor implements AppDescriptor
{
    private const int MAX_SUBJECT_RESOURCE_KEYS = 20;

    private const int MAX_ROLE_POLICIES = 8;

    /** Canonical exact descriptor identity. */
    public string $key;

    /** @var non-empty-list<string> */
    public array $supportedSubjectResourceKeys;

    /** @var non-empty-list<SignatureBulkRoleAssignmentPolicy> */
    public array $roleAssignmentPolicies;

    /** @var non-empty-list<SignatureInvitationChannel> */
    public array $supportedInvitationChannels;

    /** @var non-empty-list<SignatureAuthenticationMethod> */
    public array $supportedAuthenticationMethods;

    /**
     * @param  non-empty-list<string>  $supportedSubjectResourceKeys
     * @param  non-empty-list<SignatureBulkRoleAssignmentPolicy>  $roleAssignmentPolicies
     * @param  non-empty-list<SignatureInvitationChannel>  $supportedInvitationChannels
     * @param  non-empty-list<SignatureAuthenticationMethod>  $supportedAuthenticationMethods
     */
    public function __construct(
        public string $appKey,
        public string $bindingKey,
        public string $bindingVersion,
        public int $capabilityContractVersion,
        array $supportedSubjectResourceKeys,
        array $roleAssignmentPolicies,
        array $supportedInvitationChannels,
        array $supportedAuthenticationMethods,
        public ?string $groupSelectorResourceKey = null,
        public ?string $groupSelectorLabelKey = null,
        public bool $groupSelectorRequired = false,
        public DescriptorStatus $status = DescriptorStatus::Active,
    ) {
        if (! ResourceRef::hasCanonicalIdentity($appKey, $bindingKey) || strlen($bindingKey) > 191) {
            throw new InvalidArgumentException("Signature bulk binding [{$bindingKey}] must be owned by app [{$appKey}].");
        }
        if ($bindingVersion === '' || $bindingVersion !== trim($bindingVersion) || strlen($bindingVersion) > 32) {
            throw new InvalidArgumentException('Signature bulk binding version must be normalized, non-blank, and bounded.');
        }
        if ($capabilityContractVersion < 1) {
            throw new InvalidArgumentException('Signature bulk capability contract version must be positive.');
        }

        $subjects = [];
        if (! array_is_list($supportedSubjectResourceKeys)
            || $supportedSubjectResourceKeys === []
            || count($supportedSubjectResourceKeys) > self::MAX_SUBJECT_RESOURCE_KEYS) {
            throw new InvalidArgumentException('Signature bulk binding must declare supported subject resource keys.');
        }
        foreach ($supportedSubjectResourceKeys as $resourceKey) {
            $subjectAppKey = is_string($resourceKey) ? explode('.', $resourceKey, 2)[0] : '';
            if (! is_string($resourceKey)
                || ! ResourceRef::hasCanonicalIdentity($subjectAppKey, $resourceKey)
                || strlen($resourceKey) > 191
                || isset($subjects[$resourceKey])) {
                throw new InvalidArgumentException('Signature bulk subject resource keys must be unique canonical keys.');
            }
            $subjects[$resourceKey] = $resourceKey;
        }
        ksort($subjects, SORT_STRING);
        $this->supportedSubjectResourceKeys = array_values($subjects);

        $policies = [];
        if (! array_is_list($roleAssignmentPolicies)
            || $roleAssignmentPolicies === []
            || count($roleAssignmentPolicies) > self::MAX_ROLE_POLICIES) {
            throw new InvalidArgumentException('Signature bulk binding must declare role assignment policies.');
        }
        foreach ($roleAssignmentPolicies as $policy) {
            if (! $policy instanceof SignatureBulkRoleAssignmentPolicy || isset($policies[$policy->roleKey])) {
                throw new InvalidArgumentException('Signature bulk role assignment policies must be uniquely keyed SDK DTOs.');
            }
            $policies[$policy->roleKey] = $policy;
        }
        ksort($policies, SORT_STRING);
        $this->roleAssignmentPolicies = array_values($policies);

        $this->supportedInvitationChannels = self::canonicalEnums(
            $supportedInvitationChannels,
            SignatureInvitationChannel::class,
            'invitation channels',
        );
        $this->supportedAuthenticationMethods = self::canonicalEnums(
            $supportedAuthenticationMethods,
            SignatureAuthenticationMethod::class,
            'authentication methods',
        );

        if (($groupSelectorResourceKey === null) !== ($groupSelectorLabelKey === null)) {
            throw new InvalidArgumentException('Signature bulk group selector resource and label keys must be declared together.');
        }
        if ($groupSelectorRequired && $groupSelectorResourceKey === null) {
            throw new InvalidArgumentException('A required signature bulk group selector must declare its resource and label keys.');
        }
        if ($groupSelectorResourceKey !== null) {
            $selectorAppKey = explode('.', $groupSelectorResourceKey, 2)[0];
            if (! ResourceRef::hasCanonicalIdentity($selectorAppKey, $groupSelectorResourceKey)
                || ! in_array($groupSelectorResourceKey, $this->supportedSubjectResourceKeys, true)) {
                throw new InvalidArgumentException('Signature bulk group selector must name one supported canonical subject resource key.');
            }
            if ($groupSelectorLabelKey === ''
                || $groupSelectorLabelKey !== trim($groupSelectorLabelKey)
                || strlen($groupSelectorLabelKey) > 191) {
                throw new InvalidArgumentException('Signature bulk group selector label key must be normalized and non-blank.');
            }
        }

        $this->key = $bindingKey.'@'.$bindingVersion.'#'.$capabilityContractVersion;
    }

    /** @return non-empty-list<SignatureBulkParticipantAssignmentSource> */
    public function supportedAssignmentSources(): array
    {
        $sources = [];
        foreach ($this->roleAssignmentPolicies as $policy) {
            foreach ($policy->allowedSources as $source) {
                $sources[$source->value] = $source;
            }
        }
        ksort($sources, SORT_STRING);

        return array_values($sources);
    }

    public function policyForRole(string $roleKey): ?SignatureBulkRoleAssignmentPolicy
    {
        foreach ($this->roleAssignmentPolicies as $policy) {
            if ($policy->roleKey === $roleKey) {
                return $policy;
            }
        }

        return null;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'app_key' => $this->appKey,
            'binding_key' => $this->bindingKey,
            'binding_version' => $this->bindingVersion,
            'capability_contract_version' => $this->capabilityContractVersion,
            'supported_subject_resource_keys' => $this->supportedSubjectResourceKeys,
            'role_assignment_policies' => array_map(
                static fn (SignatureBulkRoleAssignmentPolicy $policy): array => $policy->toArray(),
                $this->roleAssignmentPolicies,
            ),
            'supported_invitation_channels' => array_map(
                static fn (SignatureInvitationChannel $channel): string => $channel->value,
                $this->supportedInvitationChannels,
            ),
            'supported_authentication_methods' => array_map(
                static fn (SignatureAuthenticationMethod $method): string => $method->value,
                $this->supportedAuthenticationMethods,
            ),
            'group_selector_resource_key' => $this->groupSelectorResourceKey,
            'group_selector_label_key' => $this->groupSelectorLabelKey,
            'group_selector_required' => $this->groupSelectorRequired,
            'status' => $this->status->value,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        $keys = [
            'app_key',
            'binding_key',
            'binding_version',
            'capability_contract_version',
            'supported_subject_resource_keys',
            'role_assignment_policies',
            'supported_invitation_channels',
            'supported_authentication_methods',
            'group_selector_resource_key',
            'group_selector_label_key',
            'group_selector_required',
            'status',
        ];
        if (array_is_list($data)
            || array_diff(array_keys($data), $keys) !== []
            || array_diff($keys, array_keys($data)) !== []) {
            throw new InvalidArgumentException('Serialized signature bulk binding descriptor shape is invalid.');
        }
        foreach (['supported_subject_resource_keys', 'role_assignment_policies', 'supported_invitation_channels', 'supported_authentication_methods'] as $key) {
            if (! is_array($data[$key]) || ! array_is_list($data[$key])) {
                throw new InvalidArgumentException("Serialized signature bulk binding [{$key}] must be a list.");
            }
        }

        return new self(
            appKey: self::string($data, 'app_key'),
            bindingKey: self::string($data, 'binding_key'),
            bindingVersion: self::string($data, 'binding_version'),
            capabilityContractVersion: self::integer($data, 'capability_contract_version'),
            supportedSubjectResourceKeys: array_map(
                static fn (mixed $key): string => is_string($key)
                    ? $key
                    : throw new InvalidArgumentException('Serialized signature bulk subject keys must be strings.'),
                $data['supported_subject_resource_keys'],
            ),
            roleAssignmentPolicies: array_map(
                static fn (mixed $policy): SignatureBulkRoleAssignmentPolicy => is_array($policy)
                    ? SignatureBulkRoleAssignmentPolicy::fromArray($policy)
                    : throw new InvalidArgumentException('Serialized signature bulk role policies must be objects.'),
                $data['role_assignment_policies'],
            ),
            supportedInvitationChannels: array_map(
                static fn (mixed $channel): SignatureInvitationChannel => is_string($channel)
                    ? SignatureInvitationChannel::from($channel)
                    : throw new InvalidArgumentException('Serialized signature bulk invitation channels must be strings.'),
                $data['supported_invitation_channels'],
            ),
            supportedAuthenticationMethods: array_map(
                static fn (mixed $method): SignatureAuthenticationMethod => is_string($method)
                    ? SignatureAuthenticationMethod::from($method)
                    : throw new InvalidArgumentException('Serialized signature bulk authentication methods must be strings.'),
                $data['supported_authentication_methods'],
            ),
            groupSelectorResourceKey: self::nullableString($data, 'group_selector_resource_key'),
            groupSelectorLabelKey: self::nullableString($data, 'group_selector_label_key'),
            groupSelectorRequired: self::boolean($data, 'group_selector_required'),
            status: DescriptorStatus::from(self::string($data, 'status')),
        );
    }

    public function descriptorKey(): string
    {
        return $this->key;
    }

    /** @template T of \BackedEnum @param array<int,mixed> $values @param class-string<T> $class @return non-empty-list<T> */
    private static function canonicalEnums(array $values, string $class, string $label): array
    {
        if (! array_is_list($values) || $values === []) {
            throw new InvalidArgumentException("Signature bulk binding must declare {$label}.");
        }
        $indexed = [];
        foreach ($values as $value) {
            if (! $value instanceof $class || isset($indexed[$value->value])) {
                throw new InvalidArgumentException("Signature bulk binding {$label} must be unique SDK enum values.");
            }
            $indexed[$value->value] = $value;
        }
        ksort($indexed, SORT_STRING);

        return array_values($indexed);
    }

    /** @param array<string,mixed> $data */
    private static function string(array $data, string $key): string
    {
        return is_string($data[$key] ?? null)
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk binding [{$key}] must be a string.");
    }

    /** @param array<string,mixed> $data */
    private static function nullableString(array $data, string $key): ?string
    {
        return $data[$key] === null || is_string($data[$key])
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk binding [{$key}] must be null or a string.");
    }

    /** @param array<string,mixed> $data */
    private static function integer(array $data, string $key): int
    {
        return is_int($data[$key] ?? null)
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk binding [{$key}] must be an integer.");
    }

    /** @param array<string,mixed> $data */
    private static function boolean(array $data, string $key): bool
    {
        return is_bool($data[$key] ?? null)
            ? $data[$key]
            : throw new InvalidArgumentException("Serialized signature bulk binding [{$key}] must be a boolean.");
    }
}
