<?php

declare(strict_types=1);

namespace Nexia\Resources;

use InvalidArgumentException;

final readonly class ResourceListQuerySchema
{
    /**
     * @param list<string> $filterKeys
     * @param list<string> $sortKeys
     */
    public function __construct(
        public array $filterKeys,
        public array $sortKeys,
        public int $defaultPerPage = 25,
        public int $minimumPerPage = 1,
        public int $maximumPerPage = 100,
    ) {
        if ($this->minimumPerPage < 1
            || $this->maximumPerPage < $this->minimumPerPage
            || $this->defaultPerPage < $this->minimumPerPage
            || $this->defaultPerPage > $this->maximumPerPage) {
            throw new InvalidArgumentException('Resource list page-size limits are invalid.');
        }
    }
}
