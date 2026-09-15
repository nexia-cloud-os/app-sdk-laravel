<?php

declare(strict_types=1);

namespace Nexia\ResourceImport\Contracts;

use Countable;
use IteratorAggregate;

/** @extends IteratorAggregate<int, array<string, mixed>> */
interface ImportAnalysisRows extends Countable, IteratorAggregate {}
