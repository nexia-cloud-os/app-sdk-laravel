<?php

declare(strict_types=1);

namespace Nexia\Settings;

use Closure;
use JsonSerializable;
use LogicException;
use SensitiveParameter;

/** Values are consumed deliberately; dumps, JSON and serialization never reveal them. */
final readonly class SettingValue implements JsonSerializable
{
    public function __construct(
        #[SensitiveParameter] private string|int|float|bool|null $value,
        public int $version,
        public bool $configured,
    ) {}

    public function use(Closure $callback): mixed
    {
        return $callback($this->value);
    }

    public function jsonSerialize(): array
    {
        return ['version' => $this->version, 'configured' => $this->configured];
    }

    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    public function __serialize(): array
    {
        throw new LogicException('Setting values cannot be serialized.');
    }
}
