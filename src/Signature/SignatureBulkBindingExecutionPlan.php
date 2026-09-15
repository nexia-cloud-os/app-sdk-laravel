<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;
use Nexia\AppDescriptors\SignatureDocumentDataFieldDescriptor;
use Nexia\ResourceReference\ResourceRef;

/**
 * App-neutral exact plan for one bulk document.
 *
 * Ordinary serialization is deliberately redacted. Classified document
 * variables and signatory role values are available only through the explicit
 * protected payload accessor intended for encrypted Core persistence.
 */
final readonly class SignatureBulkBindingExecutionPlan
{
    public const int MAX_PARTICIPANTS = 8;

    /** @var non-empty-list<SignatureBulkResolvedParticipantAssignment> */
    public array $participantAssignments;

    /** @var non-empty-list<SignatureBulkParticipantSetting> */
    public array $participantSettings;

    /** @var array<string,mixed> */
    private array $documentVariables;

    /** @var array<string,SignatureDataClassification> */
    private array $documentVariableClassifications;

    /** @var array<string,list<array<string,scalar|null>>> */
    private array $signatoryRoles;

    /**
     * @param  array<string,mixed>  $protectedDocumentVariables
     * @param  array<string,list<array<string,scalar|null>>>  $protectedSignatoryRoles
     * @param  non-empty-list<SignatureBulkResolvedParticipantAssignment>  $participantAssignments
     * @param  non-empty-list<SignatureBulkParticipantSetting>  $participantSettings
     * @param  array<string,SignatureDataClassification>  $protectedDocumentVariableClassifications
     */
    public function __construct(
        public ResourceRef $subject,
        public string $bindingKey,
        public string $bindingVersion,
        public int $capabilityContractVersion,
        public string $templateKey,
        array $protectedDocumentVariables,
        array $protectedSignatoryRoles,
        array $participantAssignments,
        array $participantSettings,
        public string $semanticFingerprint,
        array $protectedDocumentVariableClassifications = [],
    ) {
        foreach ([
            'binding_key' => [$bindingKey, 191],
            'binding_version' => [$bindingVersion, 32],
            'template_key' => [$templateKey, 160],
        ] as $field => [$value, $maximum]) {
            if ($value === '' || $value !== trim($value) || strlen($value) > $maximum) {
                throw new InvalidArgumentException("Signature bulk execution plan {$field} is invalid.");
            }
        }
        if ($capabilityContractVersion < 1 || preg_match('/\A[0-9a-f]{64}\z/D', $semanticFingerprint) !== 1) {
            throw new InvalidArgumentException('Signature bulk execution plan capability version or fingerprint is invalid.');
        }

        if (($protectedDocumentVariables !== [] && array_is_list($protectedDocumentVariables))
            || ($protectedDocumentVariableClassifications !== [] && array_is_list($protectedDocumentVariableClassifications))
            || count($protectedDocumentVariables) > SignatureDocumentDataLimits::MAX_TOP_LEVEL_FIELDS) {
            throw new InvalidArgumentException('Signature bulk protected document variables and classifications must be bounded objects.');
        }
        foreach ($protectedDocumentVariables as $key => $value) {
            if (! is_string($key) || ! SignatureDocumentDataFieldDescriptor::isCanonicalKey($key)) {
                throw new InvalidArgumentException('Signature bulk protected document variable keys must be canonical field keys.');
            }
            SignatureDocumentDataLimits::assertBoundedJsonValue($value, "Signature bulk protected document variable [{$key}]");
        }
        if (array_diff_key($protectedDocumentVariables, $protectedDocumentVariableClassifications) !== []
            || array_diff_key($protectedDocumentVariableClassifications, $protectedDocumentVariables) !== []) {
            throw new InvalidArgumentException('Every signature bulk protected document variable must carry exactly one data classification.');
        }
        foreach ($protectedDocumentVariableClassifications as $key => $classification) {
            if (! is_string($key) || ! $classification instanceof SignatureDataClassification) {
                throw new InvalidArgumentException('Signature bulk protected document variable classifications must use SDK enum values.');
            }
        }
        SignatureDocumentDataLimits::assertPayloadByteLength(
            $protectedDocumentVariables,
            SignatureDocumentDataLimits::MAX_SNAPSHOT_BYTES,
            'Signature bulk protected document variables',
        );
        self::assertSignatoryRoles($protectedSignatoryRoles);

        if (! array_is_list($participantAssignments)
            || $participantAssignments === []
            || count($participantAssignments) > self::MAX_PARTICIPANTS
            || ! array_is_list($participantSettings)
            || count($participantSettings) !== count($participantAssignments)) {
            throw new InvalidArgumentException('Signature bulk execution plan participant collections are invalid.');
        }

        $assignmentsBySlot = [];
        $recipientIdentities = [];
        foreach ($participantAssignments as $assignment) {
            if (! $assignment instanceof SignatureBulkResolvedParticipantAssignment
                || isset($assignmentsBySlot[$assignment->slotIdentity()])
                || isset($recipientIdentities[$assignment->recipientIdentity()])) {
                throw new InvalidArgumentException('Signature bulk execution plan assignments must have unique slots and recipients.');
            }
            $assignmentsBySlot[$assignment->slotIdentity()] = $assignment;
            $recipientIdentities[$assignment->recipientIdentity()] = true;
        }

        $settingsBySlot = [];
        foreach ($participantSettings as $setting) {
            if (! $setting instanceof SignatureBulkParticipantSetting || isset($settingsBySlot[$setting->slotIdentity()])) {
                throw new InvalidArgumentException('Signature bulk execution plan settings must have unique slots.');
            }
            $settingsBySlot[$setting->slotIdentity()] = $setting;
        }
        if (array_diff_key($assignmentsBySlot, $settingsBySlot) !== []
            || array_diff_key($settingsBySlot, $assignmentsBySlot) !== []) {
            throw new InvalidArgumentException('Signature bulk execution plan settings must exactly match participant assignment slots.');
        }

        $assignmentRoleCounts = [];
        foreach ($participantAssignments as $assignment) {
            $assignmentRoleCounts[$assignment->roleKey] = ($assignmentRoleCounts[$assignment->roleKey] ?? 0) + 1;
        }
        $roleCounts = array_map('count', $protectedSignatoryRoles);
        ksort($assignmentRoleCounts, SORT_STRING);
        ksort($roleCounts, SORT_STRING);
        if ($assignmentRoleCounts !== $roleCounts) {
            throw new InvalidArgumentException('Signature bulk execution plan signatory roles must exactly cover every participant assignment.');
        }

        SignatureDocumentDataLimits::assertPayloadByteLength(
            [
                'document_variables' => $protectedDocumentVariables,
                'document_variable_classifications' => array_map(
                    static fn (SignatureDataClassification $classification): string => $classification->value,
                    $protectedDocumentVariableClassifications,
                ),
                'signatory_roles' => $protectedSignatoryRoles,
            ],
            SignatureDocumentDataLimits::MAX_SNAPSHOT_BYTES,
            'Signature bulk protected execution plan',
        );

        $this->documentVariables = $protectedDocumentVariables;
        $this->documentVariableClassifications = $protectedDocumentVariableClassifications;
        $this->signatoryRoles = $protectedSignatoryRoles;
        $this->participantAssignments = array_values($participantAssignments);
        $this->participantSettings = array_map(
            static fn (SignatureBulkResolvedParticipantAssignment $assignment): SignatureBulkParticipantSetting => $settingsBySlot[$assignment->slotIdentity()],
            $participantAssignments,
        );
    }

    /** Redacted public semantic; protected document data is intentionally absent. @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'subject' => SignatureBulkGroupSelection::resourceIdentity($this->subject),
            'binding_key' => $this->bindingKey,
            'binding_version' => $this->bindingVersion,
            'capability_contract_version' => $this->capabilityContractVersion,
            'template_key' => $this->templateKey,
            'participant_assignments' => array_map(
                static fn (SignatureBulkResolvedParticipantAssignment $assignment): array => $assignment->toArray(),
                $this->participantAssignments,
            ),
            'participant_settings' => array_map(
                static fn (SignatureBulkParticipantSetting $setting): array => $setting->toArray(),
                $this->participantSettings,
            ),
            'semantic_fingerprint' => $this->semanticFingerprint,
        ];
    }

    /** Explicit encrypted-storage handoff only. @return array{document_variables:array<string,mixed>,document_variable_classifications:array<string,string>,signatory_roles:array<string,list<array<string,scalar|null>>>} */
    public function protectedPayload(): array
    {
        return [
            'document_variables' => $this->documentVariables,
            'document_variable_classifications' => array_map(
                static fn (SignatureDataClassification $classification): string => $classification->value,
                $this->documentVariableClassifications,
            ),
            'signatory_roles' => $this->signatoryRoles,
        ];
    }

    /** @param array<string,mixed> $data @param array<string,mixed> $protectedPayload */
    public static function fromArray(array $data, array $protectedPayload): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'subject',
            'binding_key',
            'binding_version',
            'capability_contract_version',
            'template_key',
            'participant_assignments',
            'participant_settings',
            'semantic_fingerprint',
        ]);
        SignatureBulkTemplateParticipantAssignment::assertExactKeys(
            $protectedPayload,
            ['document_variables', 'document_variable_classifications', 'signatory_roles'],
        );
        foreach (['subject', 'participant_assignments', 'participant_settings'] as $key) {
            if (! is_array($data[$key])) {
                throw new InvalidArgumentException("Serialized signature bulk execution plan [{$key}] must be an array.");
            }
        }
        if (! array_is_list($data['participant_assignments']) || ! array_is_list($data['participant_settings'])) {
            throw new InvalidArgumentException('Serialized signature bulk execution plan participant collections must be lists.');
        }
        if (! is_array($protectedPayload['document_variables'])
            || ! is_array($protectedPayload['document_variable_classifications'])
            || ! is_array($protectedPayload['signatory_roles'])) {
            throw new InvalidArgumentException('Serialized signature bulk protected payload is invalid.');
        }

        return new self(
            subject: SignatureBulkGroupSelection::resourceFromIdentity($data['subject']),
            bindingKey: SignatureBulkTemplateParticipantAssignment::string($data, 'binding_key'),
            bindingVersion: SignatureBulkTemplateParticipantAssignment::string($data, 'binding_version'),
            capabilityContractVersion: SignatureBulkTemplateParticipantAssignment::integer($data, 'capability_contract_version'),
            templateKey: SignatureBulkTemplateParticipantAssignment::string($data, 'template_key'),
            protectedDocumentVariables: $protectedPayload['document_variables'],
            protectedSignatoryRoles: $protectedPayload['signatory_roles'],
            participantAssignments: array_map(
                static fn (mixed $assignment): SignatureBulkResolvedParticipantAssignment => is_array($assignment)
                    ? SignatureBulkResolvedParticipantAssignment::fromArray($assignment)
                    : throw new InvalidArgumentException('Serialized signature bulk participant assignments must be objects.'),
                $data['participant_assignments'],
            ),
            participantSettings: array_map(
                static fn (mixed $setting): SignatureBulkParticipantSetting => is_array($setting)
                    ? SignatureBulkParticipantSetting::fromArray($setting)
                    : throw new InvalidArgumentException('Serialized signature bulk participant settings must be objects.'),
                $data['participant_settings'],
            ),
            semanticFingerprint: SignatureBulkTemplateParticipantAssignment::string($data, 'semantic_fingerprint'),
            protectedDocumentVariableClassifications: array_map(
                static fn (mixed $classification): SignatureDataClassification => is_string($classification)
                    ? SignatureDataClassification::from($classification)
                    : throw new InvalidArgumentException('Serialized signature bulk document variable classifications must be strings.'),
                $protectedPayload['document_variable_classifications'],
            ),
        );
    }

    /** @param array<string,mixed> $roles */
    private static function assertSignatoryRoles(array $roles): void
    {
        if (array_is_list($roles) || $roles === []) {
            throw new InvalidArgumentException('Signature bulk protected signatory roles must be a non-empty object.');
        }
        foreach ($roles as $roleKey => $subjects) {
            if (! is_string($roleKey)
                || preg_match('/\A[a-z][a-z0-9_]*\z/D', $roleKey) !== 1
                || strlen($roleKey) > 100
                || ! is_array($subjects)
                || ! array_is_list($subjects)
                || $subjects === []) {
                throw new InvalidArgumentException('Signature bulk protected signatory roles must be keyed subject lists.');
            }
            foreach ($subjects as $subject) {
                if (! is_array($subject) || array_is_list($subject) || $subject === []) {
                    throw new InvalidArgumentException('Signature bulk protected signatory role entries must be objects.');
                }
                foreach ($subject as $key => $value) {
                    if (! is_string($key) || $key === '' || $key !== trim($key) || (! is_scalar($value) && $value !== null)) {
                        throw new InvalidArgumentException('Signature bulk protected signatory role entries must contain scalar-only keyed values.');
                    }
                }
            }
        }
        SignatureDocumentDataLimits::assertPayloadByteLength(
            $roles,
            SignatureDocumentDataLimits::MAX_SNAPSHOT_BYTES,
            'Signature bulk protected signatory roles',
        );
    }
}
