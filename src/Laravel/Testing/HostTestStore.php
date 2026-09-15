<?php

declare(strict_types=1);

namespace Nexia\Laravel\Testing;

use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Nexia\Laravel\Testing\Contracts\HostTestModelStore;

/**
 * Test-only host fixture and evidence store.
 *
 * App production code must use the app-neutral directories and capabilities.
 * Core configures this seam only for test execution. It is deliberately
 * unavailable to production App code.
 */
final class HostTestStore
{
    private static ?HostTestModelStore $store = null;

    public static function configure(HostTestModelStore $store): void
    {
        self::$store = $store;
    }

    /** @return class-string */
    public static function user(): string
    {
        return self::store()->userModel();
    }

    /** @return class-string */
    public static function legalEntity(): string
    {
        return self::store()->legalEntityModel();
    }

    /** @return class-string */
    public static function operatingUnit(): string
    {
        return self::store()->operatingUnitModel();
    }

    /** @return class-string */
    public static function operatingUnitLegalEntity(): string
    {
        return self::store()->operatingUnitLegalEntityModel();
    }

    /** @return class-string */
    public static function party(): string
    {
        return self::store()->partyModel();
    }

    /** @return class-string */
    public static function tenantAppInstallation(): string
    {
        return self::store()->tenantAppInstallationModel();
    }

    /** @return class-string */
    public static function outboxMessage(): string
    {
        return self::store()->outboxMessageModel();
    }

    /** @return class-string */
    public static function workspace(): string
    {
        return self::store()->workspaceModel();
    }

    /** @return class-string */
    public static function media(): string
    {
        return self::store()->mediaModel();
    }

    public static function users(): Builder
    {
        return self::query(self::user());
    }

    public static function legalEntities(): Builder
    {
        return self::query(self::legalEntity());
    }

    public static function operatingUnits(): Builder
    {
        return self::query(self::operatingUnit());
    }

    public static function operatingUnitLegalEntities(): Builder
    {
        return self::query(self::operatingUnitLegalEntity());
    }

    public static function parties(): Builder
    {
        return self::query(self::party());
    }

    public static function tenantAppInstallations(): Builder
    {
        return self::query(self::tenantAppInstallation());
    }

    public static function outboxMessages(): Builder
    {
        return self::query(self::outboxMessage());
    }

    public static function workspaces(): Builder
    {
        return self::query(self::workspace());
    }

    /** @param class-string $model */
    private static function query(string $model): Builder
    {
        return $model::query();
    }

    private static function store(): HostTestModelStore
    {
        if (self::$store === null) {
            throw new LogicException('HostTestStore is available only after Core test bootstrap.');
        }

        return self::$store;
    }
}
