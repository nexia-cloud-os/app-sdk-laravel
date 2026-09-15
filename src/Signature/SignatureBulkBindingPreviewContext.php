<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\Tenancy\Contracts\TenantIdentity;

/** Core-restored context and normalized caller input for one group preview. */
final readonly class SignatureBulkBindingPreviewContext
{
    /** @var non-empty-list<SignatureBulkTemplateParticipantAssignment> */
    public array $templateAssignments;

    /** @var list<SignatureBulkRequestedParticipantAssignment> */
    public array $requestedAssignments;

    /** @var non-empty-list<SignatureBulkParticipantSetting> */
    public array $participantSettings;

    /**
     * @param  non-empty-list<SignatureBulkTemplateParticipantAssignment>  $templateAssignments
     * @param  list<SignatureBulkRequestedParticipantAssignment>  $requestedAssignments
     * @param  non-empty-list<SignatureBulkParticipantSetting>  $participantSettings
     */
    public function __construct(
        public TenantIdentity $tenant,
        public LegalEntity $legalEntity,
        public Actor $actor,
        public SignatureBulkTemplateProvenance $template,
        public SignatureBulkGroupSelection $selection,
        array $templateAssignments,
        array $requestedAssignments,
        array $participantSettings,
    ) {
        if (! array_is_list($templateAssignments)
            || $templateAssignments === []
            || count($templateAssignments) > SignatureBulkBindingExecutionPlan::MAX_PARTICIPANTS) {
            throw new InvalidArgumentException('Signature bulk preview requires template assignments.');
        }
        if (! array_is_list($requestedAssignments) || ! array_is_list($participantSettings) || $participantSettings === []) {
            throw new InvalidArgumentException('Signature bulk preview assignment and setting collections must be lists.');
        }

        $templateBySlot = self::uniqueBySlot(
            $templateAssignments,
            SignatureBulkTemplateParticipantAssignment::class,
            'template assignments',
        );
        $requestedBySlot = self::uniqueBySlot(
            $requestedAssignments,
            SignatureBulkRequestedParticipantAssignment::class,
            'requested assignments',
        );
        $settingsBySlot = self::uniqueBySlot(
            $participantSettings,
            SignatureBulkParticipantSetting::class,
            'participant settings',
        );

        $expectedRequested = [];
        foreach ($templateBySlot as $slot => $assignment) {
            if ($assignment->source === SignatureBulkParticipantAssignmentSource::RequestSupplied) {
                $expectedRequested[$slot] = true;
            }
        }
        if (array_diff_key($requestedBySlot, $expectedRequested) !== []
            || array_diff_key($expectedRequested, $requestedBySlot) !== []
            || array_diff_key($settingsBySlot, $templateBySlot) !== []
            || array_diff_key($templateBySlot, $settingsBySlot) !== []) {
            throw new InvalidArgumentException('Signature bulk preview must exactly fill requested assignments and every participant setting.');
        }

        $parties = [];
        foreach ($templateAssignments as $assignment) {
            if ($assignment->fixedPartyPublicId !== null) {
                if (isset($parties[$assignment->fixedPartyPublicId])) {
                    throw new InvalidArgumentException('Signature bulk preview cannot assign one Party to multiple known slots.');
                }
                $parties[$assignment->fixedPartyPublicId] = true;
            }
        }
        foreach ($requestedAssignments as $assignment) {
            if (isset($parties[$assignment->partyPublicId])) {
                throw new InvalidArgumentException('Signature bulk preview cannot assign one Party to multiple known slots.');
            }
            $parties[$assignment->partyPublicId] = true;
        }

        // Template assignment order is business-significant: providers carry
        // it into the final participant assignment/signing sequence. Slot
        // identity is used only for exact-set validation and alignment.
        $this->templateAssignments = array_values($templateAssignments);
        $this->requestedAssignments = array_map(
            static fn (string $slot): SignatureBulkRequestedParticipantAssignment => $requestedBySlot[$slot],
            array_keys($expectedRequested),
        );
        $this->participantSettings = array_map(
            static fn (string $slot): SignatureBulkParticipantSetting => $settingsBySlot[$slot],
            array_keys($templateBySlot),
        );
    }

    /**
     * @template T of object
     *
     * @param  array<int,mixed>  $values
     * @param  class-string<T>  $class
     * @return array<string,T>
     */
    private static function uniqueBySlot(array $values, string $class, string $label): array
    {
        $indexed = [];
        foreach ($values as $value) {
            if (! $value instanceof $class || ! method_exists($value, 'slotIdentity')) {
                throw new InvalidArgumentException("Signature bulk preview {$label} contain invalid DTOs.");
            }
            $slot = $value->slotIdentity();
            if (isset($indexed[$slot])) {
                throw new InvalidArgumentException("Signature bulk preview {$label} must contain unique slots.");
            }
            $indexed[$slot] = $value;
        }

        return $indexed;
    }
}
