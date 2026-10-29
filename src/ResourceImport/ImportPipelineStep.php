<?php

declare(strict_types=1);

namespace Nexia\ResourceImport;

use InvalidArgumentException;

/** A confirmed work checkpoint, or the final business result of the operation. */
final readonly class ImportPipelineStep
{
    public function __construct(
        public ?ImportBatchState $state,
        public int $processed,
        public int $total,
    ) {
        if ($processed < 0 || $total < 0 || $processed > $total) {
            throw new InvalidArgumentException('Invalid import checkpoint progress.');
        }
    }

    /** Pending checkpoints do not expose a partial review or apply eligibility. */
    public function toArray(): array
    {
        return $this->state === null
            ? ['continuation' => ['processed' => $this->processed, 'total' => $this->total]]
            : ImportPipelineWire::state($this->state);
    }
}
