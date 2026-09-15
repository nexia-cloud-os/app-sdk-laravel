<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Analysis;

use Nexia\ResourceImport\Contracts\ImportAnalysisRows;
use RuntimeException;
use Traversable;

/** Re-iterable JSON-lines scratch storage shared by all import analyzers. */
final class ImportRowSpool implements ImportAnalysisRows
{
    /** @var resource|null */
    private mixed $writer;

    private int $records = 0;

    private bool $sealed = false;

    private function __construct(private readonly string $path, mixed $writer)
    {
        $this->writer = $writer;
    }

    public static function create(): self
    {
        $path = tempnam(sys_get_temp_dir(), 'nexia-import-rows-');
        if ($path === false) {
            throw new RuntimeException('Unable to allocate an import row spool.');
        }

        $writer = @fopen($path, 'wb');
        if ($writer === false) {
            @unlink($path);

            throw new RuntimeException('Unable to open an import row spool.');
        }

        return new self($path, $writer);
    }

    /** @param array<string, mixed> $record */
    public function append(array $record): void
    {
        if ($this->sealed || ! is_resource($this->writer)) {
            throw new RuntimeException('Cannot append to a sealed import row spool.');
        }

        $bytes = json_encode(
            $record,
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION,
        )."\n";
        while ($bytes !== '') {
            $written = fwrite($this->writer, $bytes);
            if (! is_int($written) || $written < 1) {
                throw new RuntimeException('Unable to write an import row spool.');
            }
            $bytes = substr($bytes, $written);
        }

        $this->records++;
    }

    public function seal(): void
    {
        if ($this->sealed) {
            return;
        }

        if (is_resource($this->writer)) {
            fflush($this->writer);
            fclose($this->writer);
        }

        $this->writer = null;
        $this->sealed = true;
    }

    public function count(): int
    {
        return $this->records;
    }

    public function getIterator(): Traversable
    {
        $this->seal();
        $reader = @fopen($this->path, 'rb');
        if ($reader === false) {
            throw new RuntimeException('Unable to read an import row spool.');
        }

        try {
            while (($line = fgets($reader)) !== false) {
                $record = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($record)) {
                    yield $record;
                }
            }
        } finally {
            fclose($reader);
        }
    }

    public function __destruct()
    {
        if (is_resource($this->writer)) {
            fclose($this->writer);
        }

        @unlink($this->path);
    }
}
