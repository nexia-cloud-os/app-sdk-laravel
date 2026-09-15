<?php

declare(strict_types=1);

namespace Nexia\Attachments;

use InvalidArgumentException;

/** Stable file identity snapshots; these intentionally are not persistence FKs. */
final readonly class FileLifecycleSubject
{
    public function __construct(
        public string $publicId,
        public ?string $checksum = null,
        public ?string $mediaPublicId = null,
        public ?string $intakePublicId = null,
        public ?string $ownerType = null,
        public int|string|null $ownerPublicId = null,
    ) {
        if ($publicId === '' || mb_strlen($publicId) > 191) {
            throw new InvalidArgumentException('File lifecycle public identity is invalid.');
        }
        foreach ([$publicId, $checksum, $mediaPublicId, $intakePublicId, $ownerType, $ownerPublicId] as $value) {
            if ($value !== null && (! is_scalar($value) || mb_strlen((string) $value) > 191)) {
                throw new InvalidArgumentException('File lifecycle subject identity is invalid.');
            }
        }
    }
}
