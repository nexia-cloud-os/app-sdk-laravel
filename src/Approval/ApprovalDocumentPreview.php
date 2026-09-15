<?php

declare(strict_types=1);

namespace Nexia\Approval;

final readonly class ApprovalDocumentPreview
{
    /** @param list<array<string, mixed>> $sections */
    public function __construct(
        public ?string $titleKey,
        public array $sections,
        public string $schemaKey,
        public string $schemaVersion,
        public string $locale,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'title_key' => $this->titleKey,
            'sections' => $this->sections,
            'schema_key' => $this->schemaKey,
            'schema_version' => $this->schemaVersion,
            'locale' => $this->locale,
        ];
    }
}
