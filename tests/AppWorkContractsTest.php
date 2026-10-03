<?php

declare(strict_types=1);

use Nexia\AsyncWork\AppWorkCatalog;
use Nexia\AsyncWork\AppWorkDefinition;
use Nexia\AsyncWork\AppWorkInvocation;
use Nexia\AsyncWork\AppWorkSchedule;
use Nexia\AsyncWork\Contracts\AppWorkHandler;
use Nexia\AsyncWork\Contracts\AppWorkDispatcher;

require __DIR__.'/register-source-autoload.php';

final class AppWorkContractsHandler implements AppWorkHandler
{
    public function handle(AppWorkInvocation $invocation): void {}
}

final class AppWorkContractsDispatcher implements AppWorkDispatcher
{
    /** @var list<array{key: string, payload: array<string, mixed>, idempotency_key: string}> */
    public array $dispatched = [];

    public function dispatch(string $key, array $payload, string $idempotencyKey): void
    {
        $this->dispatched[] = ['key' => $key, 'payload' => $payload, 'idempotency_key' => $idempotencyKey];
    }
}

function appWorkContractMustFail(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException $exception) {
        if (! str_contains($exception->getMessage(), $message)) {
            throw new RuntimeException("Expected failure containing [{$message}], got [{$exception->getMessage()}].");
        }

        return;
    }

    throw new RuntimeException("Expected App work failure containing [{$message}].");
}

$work = new AppWorkDefinition('fixture.reconcile', AppWorkContractsHandler::class, ['background.progress', 'import.file.find']);
$schedule = new AppWorkSchedule('fixture.reconcile_hourly', 'fixture.reconcile', '0 * * * *', ['inactive' => false]);
$catalog = AppWorkCatalog::validate([$work], [$schedule], 'fixture');
assert($catalog === [
    'work' => [['key' => 'fixture.reconcile', 'platform_callbacks' => ['background.progress', 'import.file.find']]],
    'schedules' => [['key' => 'fixture.reconcile_hourly', 'work_key' => 'fixture.reconcile', 'cron' => '0 * * * *', 'payload' => ['inactive' => false]]],
]);
assert((new AppWorkInvocation('execution-1', 'fixture.reconcile', 1, ['cursor' => null]))->payload === ['cursor' => null]);
$dispatcher = new AppWorkContractsDispatcher;
$dispatcher->dispatch('fixture.reconcile', ['cursor' => null], 'fixture:cursor:root');
assert($dispatcher->dispatched === [['key' => 'fixture.reconcile', 'payload' => ['cursor' => null], 'idempotency_key' => 'fixture:cursor:root']]);
AppWorkCatalog::validateWire($catalog, 'fixture');
AppWorkCatalog::validateWire(['work' => [['key' => 'fixture.legacy']], 'schedules' => []], 'fixture');

appWorkContractMustFail(
    fn () => new AppWorkDefinition('other.reconcile', stdClass::class),
    'must implement AppWorkHandler',
);
appWorkContractMustFail(
    fn () => new AppWorkDefinition('fixture.invalid_callbacks', AppWorkContractsHandler::class, ['background.progress', 'background.progress']),
    'platform callbacks are invalid',
);
appWorkContractMustFail(
    fn () => new AppWorkSchedule('fixture.bad', 'fixture.reconcile', '@hourly'),
    'cron expression is invalid',
);
appWorkContractMustFail(
    fn () => AppWorkCatalog::validate([$work], [new AppWorkSchedule('fixture.orphan', 'fixture.missing', '0 * * * *')], 'fixture'),
    'schedule declaration is invalid',
);
appWorkContractMustFail(
    fn () => new AppWorkInvocation('execution-1', 'fixture.reconcile', 0, []),
    'attempt is invalid',
);
appWorkContractMustFail(
    fn () => AppWorkCatalog::validateWire(['work' => [['key' => 'fixture.reconcile', 'platform_callbacks' => ['background.progress', 'import.file.find']]], 'schedules' => [[
        'key' => 'fixture.orphan', 'work_key' => 'fixture.missing', 'cron' => '0 * * * *', 'payload' => [],
    ]]], 'fixture'),
    'catalog is invalid',
);

echo "App work contracts passed.\n";
