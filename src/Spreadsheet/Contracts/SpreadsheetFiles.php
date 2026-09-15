<?php

declare(strict_types=1);

namespace Nexia\Spreadsheet\Contracts;

/**
 * Host file IO for tabular data. Callers authorize file access and own mapping.
 * This API reads values, not workbook formatting or executable formulas.
 */
interface SpreadsheetFiles
{
    /**
     * CSV must already have the caller's chosen encoding/delimiter.
     * XLSX reads the named sheet, or the first sheet when omitted.
     * Keys are one-based source row numbers; values are not trimmed or cast.
     *
     * @return iterable<int, list<mixed>>
     */
    public function readRows(string $path, string $format, ?string $sheetName = null, string $delimiter = ',', bool $preserveEmptyRows = false): iterable;

    /** @param resource $stream @param iterable<list<string|int|float|bool|null>> $rows */
    public function writeCsv(mixed $stream, iterable $rows): void;

    /** Strings remain text, including strings beginning with '=' and numeric identifiers.
     * @param iterable<list<string|int|float|bool|null>> $rows
     */
    public function writeXlsx(string $path, iterable $rows): void;
}
