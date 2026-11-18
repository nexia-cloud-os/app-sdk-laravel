<?php

declare(strict_types=1);

namespace Nexia\AsyncWork;

use InvalidArgumentException;
use Nexia\AsyncWork\Contracts\AppWorkHandler;

/** App-owned work identity; the handler class remains inside the selected App. */
final readonly class AppWorkDefinition
{
    /**
     * @param class-string<AppWorkHandler> $handler
     * @param list<string> $platformCallbacks Fixed SDK callback operations required while this work runs.
     */
    public function __construct(
        public string $key,
        public string $handler,
        public array $platformCallbacks = [],
        public ?string $workConflictGroup = null,
    ) {
        if (preg_match('/\A[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $key) !== 1) {
            throw new InvalidArgumentException('App work key is invalid.');
        }
        if ($workConflictGroup !== null && ! self::validConflictGroup($workConflictGroup)) {
            throw new InvalidArgumentException('App work conflict group is invalid.');
        }
        if (! class_exists($handler) || ! is_a($handler, AppWorkHandler::class, true)) {
            throw new InvalidArgumentException('App work handler must implement AppWorkHandler.');
        }
        if (! array_is_list($platformCallbacks) || count($platformCallbacks) > 20) {
            throw new InvalidArgumentException('App work platform callbacks are invalid.');
        }
        foreach ($platformCallbacks as $callback) {
            if (! is_string($callback) || preg_match('/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+\z/D', $callback) !== 1) {
                throw new InvalidArgumentException('App work platform callbacks are invalid.');
            }
        }
        if (count(array_unique($platformCallbacks, SORT_STRING)) !== count($platformCallbacks)) {
            throw new InvalidArgumentException('App work platform callbacks are invalid.');
        }
    }

    public static function validConflictGroup(mixed $group): bool
    {
        return is_string($group) && preg_match('/\A[a-z][a-z0-9-]{0,63}\z/D', $group) === 1;
    }

    /** @return array{key: string, platform_callbacks: list<string>, conflict_group?: string} */
    public function toArray(): array
    {
        $callbacks = $this->platformCallbacks;
        sort($callbacks, SORT_STRING);

        return ['key' => $this->key, 'platform_callbacks' => $callbacks,
            ...($this->workConflictGroup === null ? [] : ['conflict_group' => $this->workConflictGroup])];
    }
}
