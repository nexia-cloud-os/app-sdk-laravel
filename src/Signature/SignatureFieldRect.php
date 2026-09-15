<?php

declare(strict_types=1);

namespace Nexia\Signature;

use InvalidArgumentException;

/** Zoom-independent page geometry stored as a 0..1 normalized rectangle. */
final readonly class SignatureFieldRect
{
    public function __construct(
        public int $page,
        public float $x,
        public float $y,
        public float $width,
        public float $height,
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException('Signature field page must be positive.');
        }

        foreach (['x' => $x, 'y' => $y] as $name => $value) {
            if (! is_finite($value) || $value < 0.0 || $value > 1.0) {
                throw new InvalidArgumentException("Signature field {$name} must be between 0 and 1.");
            }
        }

        foreach (['width' => $width, 'height' => $height] as $name => $value) {
            if (! is_finite($value) || $value <= 0.0 || $value > 1.0) {
                throw new InvalidArgumentException("Signature field {$name} must be greater than 0 and at most 1.");
            }
        }

        if ($x + $width > 1.0 || $y + $height > 1.0) {
            throw new InvalidArgumentException('Signature field rectangle must fit within its normalized page boundary.');
        }
    }

    /** @return array{page: int, x: float, y: float, width: float, height: float} */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    /** @param array{page: int, x: int|float, y: int|float, width: int|float, height: int|float} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            page: $data['page'],
            x: (float) $data['x'],
            y: (float) $data['y'],
            width: (float) $data['width'],
            height: (float) $data['height'],
        );
    }
}
