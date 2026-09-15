<?php

declare(strict_types=1);

namespace Nexia\Documents;

/** Host-owned local font that an App may embed in a generated PDF. */
final readonly class PdfFontAsset
{
    public function __construct(
        public string $family,
        public string $path,
    ) {}
}
