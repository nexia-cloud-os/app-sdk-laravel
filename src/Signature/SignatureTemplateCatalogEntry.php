<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

final readonly class SignatureTemplateCatalogEntry
{
    public function __construct(
        public string $templateKey,
        public string $name,
        public ?string $description,
    ) {
        if ($templateKey === '' || $name === '' || $templateKey !== trim($templateKey) || $name !== trim($name)) {
            throw new InvalidArgumentException('Signature template catalog entry is invalid.');
        }
    }

    /** @return array{template_key:string,name:string,description:string|null} */
    public function toArray(): array
    {
        return ['template_key' => $this->templateKey, 'name' => $this->name, 'description' => $this->description];
    }
}
