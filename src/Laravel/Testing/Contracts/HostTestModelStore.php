<?php

declare(strict_types=1);

namespace Nexia\Laravel\Testing\Contracts;

use Illuminate\Database\Eloquent\Model;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;

/**
 * Core model access that is available only while App integration tests run.
 *
 * Production App code must use host directories and capability contracts.
 */
interface HostTestModelStore
{
    /** @return class-string<Model&Actor> */
    public function userModel(): string;

    /** @return class-string<Model&LegalEntity> */
    public function legalEntityModel(): string;

    /** @return class-string<Model> */
    public function operatingUnitModel(): string;

    /** @return class-string<Model> */
    public function operatingUnitLegalEntityModel(): string;

    /** @return class-string<Model> */
    public function partyModel(): string;

    /** @return class-string<Model> */
    public function tenantAppInstallationModel(): string;

    /** @return class-string<Model> */
    public function outboxMessageModel(): string;

    /** @return class-string<Model> */
    public function workspaceModel(): string;

    /** @return class-string<Model> */
    public function mediaModel(): string;
}
