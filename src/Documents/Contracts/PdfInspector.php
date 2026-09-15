<?php

declare(strict_types=1);

namespace Nexia\Documents\Contracts;

use Nexia\Documents\PdfInspection;
use Nexia\Documents\PdfInspectionException;

/**
 * Host-local structural inspection for already authorized PDF bytes.
 *
 * Apps receive neither storage coordinates nor parser implementation details;
 * an inspection result is useful only after their own resource authorization.
 */
interface PdfInspector
{
    /** @throws PdfInspectionException */
    public function inspect(string $contents): PdfInspection;
}
