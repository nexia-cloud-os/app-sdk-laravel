<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Safe progress/result projection for an App-owned one-off PDF preparation. */
final readonly class PreparedSignableDocumentResult
{
    public function __construct(
        public string $preparedDocumentPublicId,
        public PreparedSignableDocumentStatus $status,
        public ?string $signableDocumentPublicId = null,
        public ?SignableDocumentStatus $signableDocumentStatus = null,
        public ?int $revision = null,
        public ?string $checksum = null,
        public ?int $sourcePageCount = null,
        public ?string $failureCode = null,
    ) {
        if ($preparedDocumentPublicId === '' || $preparedDocumentPublicId !== trim($preparedDocumentPublicId)) {
            throw new InvalidArgumentException('Prepared signable document result identity is invalid.');
        }
        $materialized = $status === PreparedSignableDocumentStatus::Materialized;
        if ($materialized !== ($signableDocumentPublicId !== null)
            || $materialized !== ($signableDocumentStatus !== null)
            || $materialized !== ($revision !== null)
            || $materialized !== ($sourcePageCount !== null)
            || ($revision !== null && $revision < 1)
            || ($sourcePageCount !== null && $sourcePageCount < 1)
            || ($checksum !== null && preg_match('/\A[0-9a-f]{64}\z/D', $checksum) !== 1)) {
            throw new InvalidArgumentException('Prepared signable document result shape is invalid.');
        }
    }
}
