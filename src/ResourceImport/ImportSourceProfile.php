<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

/** One vendor/ERP workbook shape accepted by an App-owned import pipeline. */
final class ImportSourceProfile
{
    /**
     * @param  array<string, list<string>>  $columnAliases  schema key → literal
     *                                                      headings printed by this source
     */
    public function __construct(
        public readonly string $key,
        public readonly string $labelKey,
        public readonly array $columnAliases,
        public readonly bool $manualMapping = false,
    ) {}
}
