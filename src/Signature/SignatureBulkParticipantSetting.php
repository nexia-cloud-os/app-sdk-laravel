<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Public authentication policy for one exact participant slot; contains no credential material. */
final readonly class SignatureBulkParticipantSetting
{
    /** @var non-empty-list<SignatureAuthenticationMethod> */
    public array $authenticationMethods;

    /** @param non-empty-list<SignatureAuthenticationMethod> $authenticationMethods */
    public function __construct(
        public string $roleKey,
        public int $participantSlot,
        public SignatureInvitationChannel $invitationChannel,
        array $authenticationMethods,
    ) {
        SignatureBulkTemplateParticipantAssignment::assertSlot($roleKey, $participantSlot);
        if (! array_is_list($authenticationMethods) || $authenticationMethods === []) {
            throw new InvalidArgumentException('Signature bulk participant setting requires authentication methods.');
        }

        $indexed = [];
        foreach ($authenticationMethods as $method) {
            if (! $method instanceof SignatureAuthenticationMethod || isset($indexed[$method->value])) {
                throw new InvalidArgumentException('Signature bulk participant authentication methods must be unique SDK enum values.');
            }
            $indexed[$method->value] = $method;
        }
        ksort($indexed, SORT_STRING);
        $this->authenticationMethods = array_values($indexed);
    }

    /** @return array{role_key:string,participant_slot:int,invitation_channel:string,authentication_methods:non-empty-list<string>} */
    public function toArray(): array
    {
        return [
            'role_key' => $this->roleKey,
            'participant_slot' => $this->participantSlot,
            'invitation_channel' => $this->invitationChannel->value,
            'authentication_methods' => array_map(
                static fn (SignatureAuthenticationMethod $method): string => $method->value,
                $this->authenticationMethods,
            ),
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys(
            $data,
            ['role_key', 'participant_slot', 'invitation_channel', 'authentication_methods'],
        );
        $methods = $data['authentication_methods'];
        if (! is_array($methods) || ! array_is_list($methods)) {
            throw new InvalidArgumentException('Serialized signature bulk authentication_methods must be a list.');
        }

        return new self(
            roleKey: SignatureBulkTemplateParticipantAssignment::string($data, 'role_key'),
            participantSlot: SignatureBulkTemplateParticipantAssignment::integer($data, 'participant_slot'),
            invitationChannel: SignatureInvitationChannel::from(
                SignatureBulkTemplateParticipantAssignment::string($data, 'invitation_channel'),
            ),
            authenticationMethods: array_map(
                static fn (mixed $method): SignatureAuthenticationMethod => is_string($method)
                    ? SignatureAuthenticationMethod::from($method)
                    : throw new InvalidArgumentException('Serialized signature bulk authentication methods must be strings.'),
                $methods,
            ),
        );
    }

    public function slotIdentity(): string
    {
        return $this->roleKey."\0".$this->participantSlot;
    }
}
