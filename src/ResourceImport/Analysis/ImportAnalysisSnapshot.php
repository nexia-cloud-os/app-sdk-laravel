<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Analysis;

use Nexia\ResourceImport\Contracts\ImportAnalysisRows;

/** Repeatable host-owned analysis snapshot returned without exposing persistence. */
final readonly class ImportAnalysisSnapshot
{
    /**
     * @param  list<string>  $headers
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $format,
        public array $headers,
        public array $metadata,
        public ImportAnalysisRows $rows,
    ) {}
}
