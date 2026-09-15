<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

final readonly class CurrentBoundSignableDocumentResult
{
    public function __construct(
        public string $publicId,
        public SignableDocumentSubmissionStatus $submissionStatus,
        public SignableDocumentStatus $documentStatus,
        public string $bindingKey,
        public string $bindingVersion,
        public string $templateKey,
        public int $templateVersion,
        public int $revision,
    ) {
        foreach ([$publicId, $bindingKey, $bindingVersion, $templateKey] as $value) {
            if ($value === '' || $value !== trim($value)) {
                throw new InvalidArgumentException('Signable document result identities must be normalized and non-blank.');
            }
        }
        if ($templateVersion < 1 || $revision < 1) {
            throw new InvalidArgumentException('Signable document result versions must be positive.');
        }
    }
}
