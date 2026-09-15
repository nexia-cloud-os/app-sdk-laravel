<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Analysis;

use Nexia\ResourceImport\Contracts\ImportAnalysisRows;

/** App-neutral parser result persisted by the host as encrypted chunks. */
final readonly class ImportAnalysisResult
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
