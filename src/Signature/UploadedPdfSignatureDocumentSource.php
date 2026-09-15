<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** One uploaded PDF with its complete interactive participant and field plan. */
final readonly class UploadedPdfSignatureDocumentSource implements SignatureDocumentPlanSource
{
    /**
     * @param non-empty-list<SignatureParticipantSnapshot> $participants
     * @param non-empty-list<SignatureFieldDefinition> $fields
     */
    public function __construct(
        public string $uploadIntentId,
        public array $participants,
        public array $fields,
    ) {
        if (preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/Di', $uploadIntentId) !== 1
            || ! array_is_list($participants) || $participants === []
            || ! array_is_list($fields) || $fields === []) {
            throw new InvalidArgumentException('Uploaded PDF signature document source is invalid.');
        }
        foreach ($participants as $participant) {
            if (! $participant instanceof SignatureParticipantSnapshot) {
                throw new InvalidArgumentException('Uploaded PDF signature participants are invalid.');
            }
        }
        foreach ($fields as $field) {
            if (! $field instanceof SignatureFieldDefinition) {
                throw new InvalidArgumentException('Uploaded PDF signature fields are invalid.');
            }
        }
    }

    public function kind(): SignatureDocumentSourceKind
    {
        return SignatureDocumentSourceKind::UploadedPdf;
    }
}
