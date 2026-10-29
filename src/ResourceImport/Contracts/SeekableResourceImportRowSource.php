<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

/** Repeatable bounded reads without replaying or materializing earlier rows. */
interface SeekableResourceImportRowSource extends ResourceImportRowSource
{
    /**
     * Offsets are zero-based data-row positions, independent of spreadsheet line
     * numbers. Preserve the original iterator keys and any supplied __line.
     *
     * @return iterable<int, array<string, mixed>>
     */
    public function slice(int $offset, int $limit): iterable;
}
