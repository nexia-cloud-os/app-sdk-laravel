<?php

declare(strict_types=1);

namespace Nexia\Signature;

use DateTimeImmutable;
use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceRef;

/**
 * An App-authorized canonical snapshot for a request-local editing session.
 *
 * `canonicalVariables` are evidence copied into the eventual document, not an
 * editable Core-owned projection of the App's business record. An App that
 * wants to accept an edited business value must do so through its own command
 * and begin a new preparation from its refreshed canonical snapshot.
 */
final readonly class SignatureRequestPreparationSubmission
{
    /**
     * @param  array<string, mixed>  $canonicalVariables
     * @param  list<array<string, scalar|null>>  $participantDraftSnapshot
     */
    public function __construct(
        public LegalEntity $legalEntity,
        public Actor $actor,
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public string $templateKey,
        public string $locale,
        public string $effectiveOn,
        public array $canonicalVariables,
        public array $participantDraftSnapshot,
    ) {
        foreach ([
            'bindingKey' => $bindingKey,
            'bindingVersion' => $bindingVersion,
            'templateKey' => $templateKey,
            'locale' => $locale,
        ] as $field => $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException("Signature request preparation {$field} must be normalized and non-blank.");
            }
        }
        if (strlen($bindingKey) > 191
            || strlen($bindingVersion) > 32
            || strlen($templateKey) > 160
            || strlen($locale) > 20
            || preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){0,2}\z/D', $locale) !== 1
            || ($canonicalVariables !== [] && array_is_list($canonicalVariables))
            || ! array_is_list($participantDraftSnapshot)) {
            throw new InvalidArgumentException('Signature request preparation submission is invalid.');
        }
        foreach ($participantDraftSnapshot as $participant) {
            if (array_is_list($participant)) {
                throw new InvalidArgumentException('Signature request preparation participants must be object snapshots.');
            }
            foreach ($participant as $key => $value) {
                if (! is_string($key) || (! is_scalar($value) && $value !== null)) {
                    throw new InvalidArgumentException('Signature request preparation participant snapshots must be scalar-only.');
                }
            }
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $effectiveOn);
        if (! $date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $effectiveOn) {
            throw new InvalidArgumentException('Signature request preparation effectiveOn must be an ISO date.');
        }
    }
}
