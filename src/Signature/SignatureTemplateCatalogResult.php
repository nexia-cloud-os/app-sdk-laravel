<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

final readonly class SignatureTemplateCatalogResult
{
    /** @param list<SignatureTemplateCatalogEntry> $templates */
    public function __construct(public array $templates)
    {
        if (! array_is_list($templates)) {
            throw new InvalidArgumentException('Signature template catalog result must be a list.');
        }
        foreach ($templates as $template) {
            if (! $template instanceof SignatureTemplateCatalogEntry) {
                throw new InvalidArgumentException('Signature template catalog result entry is invalid.');
            }
        }
    }

    /** @return list<array{template_key:string,name:string,description:string|null}> */
    public function toArray(): array
    {
        return array_map(static fn (SignatureTemplateCatalogEntry $entry): array => $entry->toArray(), $this->templates);
    }
}
