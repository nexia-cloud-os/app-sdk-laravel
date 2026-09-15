<?php

declare(strict_types=1);

namespace Nexia\Approval;

/** Host-selected approval route identity exposed without the Core route model. */
final readonly class ApprovalRouteReference
{
    public function __construct(
        public string $publicId,
        public string $key,
        public int $version,
        public string $label,
        public ?int $legalEntityId,
    ) {}

    /** @return array{key: string, public_id: string, version: int, label: string, legal_entity_id: int|null} */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'public_id' => $this->publicId,
            'version' => $this->version,
            'label' => $this->label,
            'legal_entity_id' => $this->legalEntityId,
        ];
    }
}
