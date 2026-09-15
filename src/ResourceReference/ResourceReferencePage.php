<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

use InvalidArgumentException;

/** One owner-authorized selector result page with real pagination totals. */
final readonly class ResourceReferencePage
{
    /** @param list<ResolvedResourceReference> $items */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {
        $itemCount = count($this->items);
        if ($this->currentPage < 1
            || $this->lastPage !== max(1, (int) ceil($this->total / max(1, $this->perPage)))
            || $this->perPage < 1
            || $this->total < 0
            || $itemCount > $this->perPage
            || $itemCount > $this->total
            || ($this->currentPage > $this->lastPage && $itemCount > 0)) {
            throw new InvalidArgumentException('Resource Reference pagination metadata is invalid.');
        }

        foreach ($this->items as $item) {
            if (! $item instanceof ResolvedResourceReference) {
                throw new InvalidArgumentException('Resource Reference pages accept resolved references only.');
            }
        }
    }

    public static function empty(int $page, int $perPage): self
    {
        return new self([], max(1, $page), 1, max(1, $perPage), 0);
    }

}
