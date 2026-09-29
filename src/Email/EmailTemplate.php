<?php

declare(strict_types=1);

namespace Nexia\Email;

/** App-owned starter. Applying it copies editable content into a process draft. */
final readonly class EmailTemplate
{
    /** @param array<string, string> $fields Field key => localized label key. */
    public function __construct(
        public string $key,
        public int $version,
        public string $labelKey,
        public string $subjectKey,
        public string $bodyKey,
        public array $fields,
        public bool $requiresSubject = false,
    ) {}
}
