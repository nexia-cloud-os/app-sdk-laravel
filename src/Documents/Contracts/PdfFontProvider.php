<?php

declare(strict_types=1);

namespace Nexia\Documents\Contracts;

use Nexia\Documents\PdfFontAsset;

/** Resolves a host-bundled local font without exposing Core paths to Apps. */
interface PdfFontProvider
{
    public function forLocale(string $locale): ?PdfFontAsset;
}
