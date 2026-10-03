<?php

declare(strict_types=1);

namespace Nexia\AsyncWork;

use InvalidArgumentException;

/** Validates one App's work declarations before catalog export or execution. */
final class AppWorkCatalog
{
    /**
     * @param list<AppWorkDefinition> $work
     * @param list<AppWorkSchedule> $schedules
     * @return array{work: list<array{key: string, platform_callbacks: list<string>}>, schedules: list<array{key: string, work_key: string, cron: string, payload: array<string, mixed>}>}
     */
    public static function validate(array $work, array $schedules, string $appKey): array
    {
        if (preg_match('/\A[a-z][a-z0-9-]*\z/D', $appKey) !== 1) {
            throw new InvalidArgumentException('App work owner is invalid.');
        }
        $prefix = $appKey.'.';
        $definitions = [];
        foreach ($work as $definition) {
            if (! $definition instanceof AppWorkDefinition || ! str_starts_with($definition->key, $prefix)
                || isset($definitions[$definition->key]) || count($definitions) >= 1000) {
                throw new InvalidArgumentException('App work declaration is invalid.');
            }
            $definitions[$definition->key] = $definition;
        }
        $declaredSchedules = [];
        foreach ($schedules as $schedule) {
            if (! $schedule instanceof AppWorkSchedule || ! str_starts_with($schedule->key, $prefix)
                || ! isset($definitions[$schedule->workKey]) || isset($declaredSchedules[$schedule->key])
                || count($declaredSchedules) >= 1000) {
                throw new InvalidArgumentException('App work schedule declaration is invalid.');
            }
            $declaredSchedules[$schedule->key] = $schedule;
        }
        ksort($definitions, SORT_STRING);
        ksort($declaredSchedules, SORT_STRING);

        return [
            'work' => array_map(static fn (AppWorkDefinition $definition): array => $definition->toArray(), array_values($definitions)),
            'schedules' => array_map(static fn (AppWorkSchedule $schedule): array => $schedule->toArray(), array_values($declaredSchedules)),
        ];
    }

    /**
     * @param array{work?: mixed, schedules?: mixed} $catalog
     */
    public static function validateWire(array $catalog, string $appKey): void
    {
        if (preg_match('/\A[a-z][a-z0-9-]*\z/D', $appKey) !== 1
            || ! is_array($catalog['work'] ?? null) || ! array_is_list($catalog['work'])
            || ! is_array($catalog['schedules'] ?? null) || ! array_is_list($catalog['schedules'])) {
            throw new InvalidArgumentException('App work catalog is invalid.');
        }
        $prefix = $appKey.'.';
        $work = [];
        foreach ($catalog['work'] as $definition) {
            $callbacks = is_array($definition) ? ($definition['platform_callbacks'] ?? []) : null;
            if (! is_array($definition) || array_diff(array_keys($definition), ['key', 'platform_callbacks']) !== []
                || ! is_string($definition['key']) || ! is_array($callbacks) || ! array_is_list($callbacks)
                || count($callbacks) > 20 || ! str_starts_with($definition['key'], $prefix)
                || preg_match('/\A[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9_-]*)+\z/D', $definition['key']) !== 1
                || isset($work[$definition['key']]) || count($work) >= 1000) {
                throw new InvalidArgumentException('App work catalog is invalid.');
            }
            foreach ($callbacks as $callback) {
                if (! is_string($callback) || preg_match('/\A[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+\z/D', $callback) !== 1) {
                    throw new InvalidArgumentException('App work catalog is invalid.');
                }
            }
            if (count(array_unique($callbacks, SORT_STRING)) !== count($callbacks)) {
                throw new InvalidArgumentException('App work catalog is invalid.');
            }
            $work[$definition['key']] = true;
        }
        $schedules = [];
        foreach ($catalog['schedules'] as $schedule) {
            if (! is_array($schedule) || array_keys($schedule) !== ['key', 'work_key', 'cron', 'payload']
                || ! is_string($schedule['key']) || ! str_starts_with($schedule['key'], $prefix)
                || ! is_string($schedule['work_key']) || ! isset($work[$schedule['work_key']])
                || ! is_string($schedule['cron']) || ! is_array($schedule['payload']) || isset($schedules[$schedule['key']])
                || count($schedules) >= 1000) {
                throw new InvalidArgumentException('App work catalog is invalid.');
            }
            new AppWorkSchedule($schedule['key'], $schedule['work_key'], $schedule['cron'], $schedule['payload']);
            $schedules[$schedule['key']] = true;
        }
    }
}
