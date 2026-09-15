<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Exact immutable template revision selected for one bulk draft. */
final readonly class SignatureBulkTemplateProvenance
{
    public function __construct(
        public string $templatePublicId,
        public int $templateRevision,
        public string $templateKey,
        public string $templateContentHash,
        public string $bindingKey,
        public string $bindingVersion,
    ) {
        foreach ([
            'template_public_id' => [$templatePublicId, 191],
            'template_key' => [$templateKey, 160],
            'binding_key' => [$bindingKey, 191],
            'binding_version' => [$bindingVersion, 32],
        ] as $field => [$value, $maximum]) {
            if ($value === '' || $value !== trim($value) || strlen($value) > $maximum) {
                throw new InvalidArgumentException("Signature bulk template provenance {$field} is invalid.");
            }
        }
        if ($templateRevision < 1 || preg_match('/\A[0-9a-f]{64}\z/D', $templateContentHash) !== 1) {
            throw new InvalidArgumentException('Signature bulk template revision and content hash are invalid.');
        }
    }

    /** @return array{template_public_id:string,template_revision:int,template_key:string,template_content_hash:string,binding_key:string,binding_version:string} */
    public function toArray(): array
    {
        return [
            'template_public_id' => $this->templatePublicId,
            'template_revision' => $this->templateRevision,
            'template_key' => $this->templateKey,
            'template_content_hash' => $this->templateContentHash,
            'binding_key' => $this->bindingKey,
            'binding_version' => $this->bindingVersion,
        ];
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        SignatureBulkTemplateParticipantAssignment::assertExactKeys($data, [
            'template_public_id',
            'template_revision',
            'template_key',
            'template_content_hash',
            'binding_key',
            'binding_version',
        ]);

        return new self(
            templatePublicId: SignatureBulkTemplateParticipantAssignment::string($data, 'template_public_id'),
            templateRevision: SignatureBulkTemplateParticipantAssignment::integer($data, 'template_revision'),
            templateKey: SignatureBulkTemplateParticipantAssignment::string($data, 'template_key'),
            templateContentHash: SignatureBulkTemplateParticipantAssignment::string($data, 'template_content_hash'),
            bindingKey: SignatureBulkTemplateParticipantAssignment::string($data, 'binding_key'),
            bindingVersion: SignatureBulkTemplateParticipantAssignment::string($data, 'binding_version'),
        );
    }
}
