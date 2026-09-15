<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/**
 * Immutable recipient data submitted by an App. `toArray()` is transport data
 * and contains contact data; use `toLogSafeArray()` for logs and diagnostics.
 */
final readonly class SignatureParticipantSnapshot
{
    /**
     * @param  list<string>  $assignedFieldKeys
     * @param  non-empty-list<SignatureAuthenticationMethod>  $requiredMethods
     */
    public function __construct(
        public string $roleKey,
        public int $sequence,
        public array $assignedFieldKeys,
        public string $displayName,
        public string $locale,
        public SignatureInvitationChannel $invitationChannel,
        public string $invitationAddress,
        public SignatureAuthenticationProfileReference $authenticationProfile,
        public array $requiredMethods,
        public ?string $emailOtpChallengeAddress = null,
        /**
         * An Argon2id verifier used only while an App submits a new request.
         * It is deliberately excluded from serialized and log-safe payloads.
         */
        public ?SignatureRequestPasswordVerifier $requestPasswordVerifier = null,
        /**
         * Optional App-neutral identity assertion for an internal person Party.
         * Core must re-resolve and validate it; the identifier grants no access.
         */
        public ?string $partyPublicId = null,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1 || $sequence < 1) {
            throw new InvalidArgumentException('Signature participant role and sequence are invalid.');
        }

        foreach (['displayName' => $displayName, 'locale' => $locale, 'invitationAddress' => $invitationAddress] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature participant {$field} must be normalized and non-blank.");
            }
        }

        if (strlen($displayName) > 255
            || strlen($locale) > 20
            || strlen($invitationAddress) > 320
            || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,2}\z/D', $locale) !== 1) {
            throw new InvalidArgumentException('Signature participant contains an oversized or invalid identity.');
        }

        if (! array_is_list($assignedFieldKeys)) {
            throw new InvalidArgumentException('Signature participant field assignments must be a list.');
        }

        $seenFieldKeys = [];
        foreach ($assignedFieldKeys as $fieldKey) {
            if (! is_string($fieldKey) || $fieldKey === '' || $fieldKey !== trim($fieldKey)) {
                throw new InvalidArgumentException('Signature participant field assignments must contain normalized non-blank keys.');
            }
            if (isset($seenFieldKeys[$fieldKey])) {
                throw new InvalidArgumentException('Signature participant field assignments must be unique.');
            }
            $seenFieldKeys[$fieldKey] = true;
        }

        if (! array_is_list($requiredMethods) || $requiredMethods === []) {
            throw new InvalidArgumentException('Signature participant must require at least one authentication method.');
        }

        $methodValues = [];
        foreach ($requiredMethods as $method) {
            if (! $method instanceof SignatureAuthenticationMethod) {
                throw new InvalidArgumentException('Signature participant authentication methods must use the SDK enum.');
            }
            if (isset($methodValues[$method->value])) {
                throw new InvalidArgumentException('Signature participant authentication methods must be unique.');
            }
            $methodValues[$method->value] = true;
        }

        $requiresEmailOtp = isset($methodValues[SignatureAuthenticationMethod::EmailOtp->value]);
        if ($requiresEmailOtp !== ($emailOtpChallengeAddress !== null)) {
            throw new InvalidArgumentException('Signature participant must provide an email OTP address exactly when email OTP is required.');
        }

        if ($emailOtpChallengeAddress !== null
            && ($emailOtpChallengeAddress === ''
                || $emailOtpChallengeAddress !== trim($emailOtpChallengeAddress)
                || strlen($emailOtpChallengeAddress) > 320)) {
            throw new InvalidArgumentException('Signature participant email OTP address must be normalized and non-blank.');
        }

        if ($partyPublicId !== null
            && (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/D', $partyPublicId) !== 1
                || strlen($partyPublicId) > 191)) {
            throw new InvalidArgumentException('Signature participant Party public ID must be a canonical UUID.');
        }

    }

    /**
     * @return array{
     *   role_key: string,
     *   sequence: int,
     *   assigned_field_keys: list<string>,
     *   display_name: string,
     *   locale: string,
     *   invitation_channel: string,
     *   invitation_address: string,
     *   authentication_profile: array{profile_key: string, version: string},
     *   required_methods: non-empty-list<string>,
     *   email_otp_challenge_address: string|null,
     *   party_public_id?: string
     * }
     */
    public function toArray(): array
    {
        $snapshot = [
            'role_key' => $this->roleKey,
            'sequence' => $this->sequence,
            'assigned_field_keys' => $this->assignedFieldKeys,
            'display_name' => $this->displayName,
            'locale' => $this->locale,
            'invitation_channel' => $this->invitationChannel->value,
            'invitation_address' => $this->invitationAddress,
            'authentication_profile' => $this->authenticationProfile->toArray(),
            'required_methods' => array_map(
                static fn (SignatureAuthenticationMethod $method): string => $method->value,
                $this->requiredMethods,
            ),
            'email_otp_challenge_address' => $this->emailOtpChallengeAddress,
        ];

        if ($this->partyPublicId !== null) {
            $snapshot['party_public_id'] = $this->partyPublicId;
        }

        return $snapshot;
    }

    /**
     * @return array{
     *   role_key: string,
     *   sequence: int,
     *   assigned_field_keys: list<string>,
     *   locale: string,
     *   invitation_channel: string,
     *   authentication_profile: array{profile_key: string, version: string},
     *   required_methods: non-empty-list<string>
     * }
     */
    public function toLogSafeArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'sequence' => $this->sequence,
            'assigned_field_keys' => $this->assignedFieldKeys,
            'locale' => $this->locale,
            'invitation_channel' => $this->invitationChannel->value,
            'authentication_profile' => $this->authenticationProfile->toArray(),
            'required_methods' => array_map(
                static fn (SignatureAuthenticationMethod $method): string => $method->value,
                $this->requiredMethods,
            ),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $profile = $data['authentication_profile'] ?? null;
        $requiredMethods = $data['required_methods'] ?? null;
        $assignedFieldKeys = $data['assigned_field_keys'] ?? null;

        if (! is_array($profile)
            || ! is_array($requiredMethods)
            || ! array_is_list($requiredMethods)
            || ! is_array($assignedFieldKeys)
            || ! array_is_list($assignedFieldKeys)) {
            throw new InvalidArgumentException('Signature participant serialization is invalid.');
        }

        $methods = [];
        foreach ($requiredMethods as $method) {
            if (! is_string($method)) {
                throw new InvalidArgumentException('Signature participant serialized authentication methods must be strings.');
            }
            $methods[] = SignatureAuthenticationMethod::from($method);
        }

        return new self(
            roleKey: $data['role_key'],
            sequence: $data['sequence'],
            assignedFieldKeys: $assignedFieldKeys,
            displayName: $data['display_name'],
            locale: $data['locale'],
            invitationChannel: SignatureInvitationChannel::from($data['invitation_channel']),
            invitationAddress: $data['invitation_address'],
            authenticationProfile: SignatureAuthenticationProfileReference::fromArray($profile),
            requiredMethods: $methods,
            emailOtpChallengeAddress: $data['email_otp_challenge_address'] ?? null,
            partyPublicId: array_key_exists('party_public_id', $data)
                ? (is_string($data['party_public_id'])
                    ? $data['party_public_id']
                    : throw new InvalidArgumentException('Signature participant Party public ID must be a string.'))
                : null,
        );
    }
}
