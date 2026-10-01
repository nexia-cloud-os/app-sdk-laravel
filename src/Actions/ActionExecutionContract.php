<?php

declare(strict_types=1);

namespace Nexia\Actions;

use InvalidArgumentException;

/** Declaration only; hosts must reject unavailable execution, never return fake success. */
final readonly class ActionExecutionContract
{
    public function __construct(
        public bool $available = false,
        public string $mode = 'sync',
        public string $concurrency = 'record-version',
        public string $replay = 'idempotency-key',
        public array $resultSchema = ['type' => 'object'],
    ) {
        if (! in_array($mode, ['sync', 'async'], true)
            || ! in_array($concurrency, ['none', 'record-version'], true)
            || ! in_array($replay, ['none', 'idempotency-key'], true)
            || ($resultSchema['type'] ?? null) !== 'object') {
            throw new InvalidArgumentException('Invalid action execution contract.');
        }
        if ($available) {
            throw new InvalidArgumentException('Versioned action execution is not supported yet.');
        }
    }

    public function toArray(): array
    {
        return ['version' => 1, 'available' => $this->available, 'mode' => $this->mode,
            'concurrency' => $this->concurrency, 'replay' => $this->replay, 'result_schema' => $this->resultSchema,
            'outcomes' => ['completed', 'accepted', 'failed', 'uncertain']];
    }

    public static function fromArray(array $value): self
    {
        if (count($value) !== 7 || array_diff(array_keys($value), ['version', 'available', 'mode', 'concurrency', 'replay', 'result_schema', 'outcomes']) !== []
            || ($value['version'] ?? null) !== 1 || ($value['outcomes'] ?? null) !== ['completed', 'accepted', 'failed', 'uncertain']) {
            throw new InvalidArgumentException('Unsupported action result contract.');
        }

        return new self($value['available'], $value['mode'], $value['concurrency'], $value['replay'], $value['result_schema']);
    }
}
