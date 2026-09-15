<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Countable;
use IteratorAggregate;

/**
 * Repeatable mapped rows supplied by the host without one process-wide array.
 *
 * Every iterator and chunk traversal starts from the first row. Apps may make
 * more than one validation pass, but must not retain the whole source merely to
 * do so.
 *
 * @extends IteratorAggregate<int, array<string, mixed>>
 */
interface ResourceImportRowSource extends Countable, IteratorAggregate
{
    /**
     * @return iterable<int, list<array<string, mixed>>>
     */
    public function chunks(int $size): iterable;
}
