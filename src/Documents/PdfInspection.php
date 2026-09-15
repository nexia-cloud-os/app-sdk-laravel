<?php

declare(strict_types=1);

namespace Nexia\Documents;

use InvalidArgumentException;

/** Safe structural facts derived from a locally parsed PDF byte stream. */
final readonly class PdfInspection
{
    public function __construct(
        public int $pageCount,
        public string $checksum,
    ) {
        if ($pageCount < 1
            || preg_match('/\A[0-9a-f]{64}\z/D', $checksum) !== 1) {
            throw new InvalidArgumentException('PDF inspection result is invalid.');
        }
    }
}
