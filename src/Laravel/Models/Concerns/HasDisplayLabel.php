<?php

declare(strict_types=1);

namespace Nexia\Laravel\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Exposes one canonical, locale-aware human label as `display_label`.
 *
 * @mixin Model
 */
trait HasDisplayLabel
{
    public function initializeHasDisplayLabel(): void
    {
        $this->appends = array_values(array_unique(array_merge(
            $this->appends ?? [],
            ['display_label'],
        )));
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->displayLabel();
    }

    abstract public function displayLabel(): string;
}
