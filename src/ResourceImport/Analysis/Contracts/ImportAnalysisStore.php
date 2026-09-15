<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Analysis\Contracts;

use Closure;
use Nexia\Attachments\AuthorizedImportFile;
use Nexia\Identity\Contracts\Actor;
use Nexia\ResourceImport\Analysis\ImportAnalysisResult;
use Nexia\ResourceImport\Analysis\ImportAnalysisSnapshot;

/** Host persistence for short-lived, encrypted bulk-file parser results. */
interface ImportAnalysisStore
{
    /** @param Closure(): ImportAnalysisResult $analyze */
    public function readThrough(
        AuthorizedImportFile $file,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
        string $analysisKind,
        int $parserVersion,
        Closure $analyze,
    ): ImportAnalysisSnapshot;

    public function find(
        AuthorizedImportFile $file,
        string $resourceKey,
        int|string $legalEntityKey,
        Actor $actor,
        string $analysisKind,
        int $parserVersion,
    ): ?ImportAnalysisSnapshot;
}
