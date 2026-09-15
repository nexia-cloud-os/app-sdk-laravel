<?php

declare(strict_types=1);

namespace Nexia\Approval\Domain\Form;

/**
 * Immutable persisted envelope for one submitted approval form.
 */
final readonly class SubmittedFormSnapshot
{
    public function __construct(
        public string $templateKey,
        public int $templateVersion,
        public string $body,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            templateKey: (string) ($data['template_key'] ?? ''),
            templateVersion: (int) ($data['template_version'] ?? 0),
            body: (string) ($data['body'] ?? ''),
        );
    }

    /**
     * @return array{template_key: string, template_version: int, body: string}
     */
    public function toArray(): array
    {
        return [
            'template_key' => $this->templateKey,
            'template_version' => $this->templateVersion,
            'body' => $this->body,
        ];
    }
}
