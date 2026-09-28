<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/** Private authenticated host transport. Never use this payload for logs or browser responses. */
final class SignatureRequestWire
{
    public static function submission(SignatureRequestSubmission $submission): array
    {
        $verifiers = [];
        foreach ($submission->participants as $participant) {
            if ($participant->requestPasswordVerifier !== null) {
                $verifiers[$participant->sequence] = $participant->requestPasswordVerifier->valueForSignatureHost();
            }
        }

        return [...$submission->toArray(), 'request_password_verifiers' => $verifiers];
    }

    public static function restoreSubmission(array $data, LegalEntity $entity, Actor $actor): SignatureRequestSubmission
    {
        $submission = SignatureRequestSubmission::fromArray($data, $entity, $actor);
        $verifiers = $data['request_password_verifiers'] ?? [];
        if (! is_array($verifiers) || count($verifiers) > count($submission->participants)) {
            throw new InvalidArgumentException('Invalid signature credential handoff.');
        }
        $participants = [];
        foreach ($submission->participants as $participant) {
            if (! array_key_exists($participant->sequence, $verifiers)) {
                $participants[] = $participant;
                continue;
            }
            $value = $verifiers[$participant->sequence];
            if (! is_string($value) || ! in_array(SignatureAuthenticationMethod::RequestPassword, $participant->requiredMethods, true)) {
                throw new InvalidArgumentException('Signature credential does not match its participant.');
            }
            $participants[] = new SignatureParticipantSnapshot(...[
                ...get_object_vars($participant),
                'requestPasswordVerifier' => SignatureRequestPasswordVerifier::fromHandoffValue($value),
            ]);
            unset($verifiers[$participant->sequence]);
        }
        if ($verifiers !== []) {
            throw new InvalidArgumentException('Signature credential has no matching participant.');
        }

        return new SignatureRequestSubmission(...[...get_object_vars($submission), 'participants' => $participants]);
    }
}
