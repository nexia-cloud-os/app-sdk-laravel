<?php

declare(strict_types=1);

namespace Nexia\ResourceTransfer\Contracts;

/**
 * Public shape exposed by the host when an App resource supports data transfer.
 *
 * Core owns discovery, authorization, and execution. Apps use this contract to
 * recognize an available definition without depending on that implementation.
 */
interface ResourceTransferDefinition
{
    public const IMPORT_MODE_CREATE_ONLY = 'createOnly';

    public const SCOPE_TENANT = 'tenant';

    public const SCOPE_LEGAL_ENTITY = 'legal_entity';

    public const SCOPE_OPERATING_UNIT = 'operating_unit';

    public function supportsImport(): bool;

    public function usesContributedExportSource(): bool;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
