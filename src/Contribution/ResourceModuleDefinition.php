<?php

declare(strict_types=1);

namespace Nexia\Contribution;

use InvalidArgumentException;
use Nexia\AppDescriptors\ResourceDescriptor;

/**
 * App-neutral definition of one Resource's module contribution.
 *
 * The owning App keeps its navigation, permission, authorization, model, and
 * shell declarations on the concrete module class. This value binds that
 * Resource identity to its canonical App-owned label and optional public
 * descriptor without restating domain vocabulary in the SDK.
 */
final readonly class ResourceModuleDefinition
{
    private function __construct(
        public string $resourceKey,
        public ?ResourceDescriptor $descriptor,
        private ?string $labelKey,
    ) {
        if (trim($this->resourceKey) === '') {
            throw new InvalidArgumentException('A Resource Module requires a non-empty Resource Key.');
        }

        if ($this->descriptor !== null && $this->descriptor->key !== $this->resourceKey) {
            throw new InvalidArgumentException(
                "Resource Module [{$this->resourceKey}] cannot publish descriptor [{$this->descriptor->key}].",
            );
        }

        if ($this->labelKey !== null && trim($this->labelKey) === '') {
            throw new InvalidArgumentException('A Resource Module label key cannot be empty.');
        }

        if ($this->descriptor?->labelKey !== null
            && $this->labelKey !== null
            && $this->descriptor->labelKey !== $this->labelKey) {
            throw new InvalidArgumentException(
                "Resource Module [{$this->resourceKey}] cannot declare conflicting label keys.",
            );
        }
    }

    public static function fromDescriptor(ResourceDescriptor $descriptor): self
    {
        return new self($descriptor->key, $descriptor, $descriptor->labelKey);
    }

    public static function withoutDescriptor(string $resourceKey, ?string $labelKey = null): self
    {
        return new self($resourceKey, null, $labelKey);
    }

    public function publicDescriptor(): ?ResourceDescriptor
    {
        return $this->descriptor;
    }

    /** Canonical i18n key naming the Resource independently of any screen. */
    public function labelKey(): ?string
    {
        return $this->labelKey;
    }
}
