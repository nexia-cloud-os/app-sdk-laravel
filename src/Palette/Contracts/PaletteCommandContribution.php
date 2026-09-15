<?php

declare(strict_types=1);

namespace Nexia\Palette\Contracts;

/** Contributes executable commands to the host command palette. */
interface PaletteCommandContribution
{
    /**
     * @return list<PaletteCommand>
     */
    public static function paletteCommandItems(): array;
}
