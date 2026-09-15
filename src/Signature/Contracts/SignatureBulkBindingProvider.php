<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureBulkBindingExecutionContext;
use Nexia\Signature\SignatureBulkBindingPreviewContext;
use Nexia\Signature\SignatureBulkBindingPreviewResult;
use Nexia\Signature\SignatureBulkBindingReauthorizationResult;

/** App-owned resolver for one exact Group Bulk binding capability. */
interface SignatureBulkBindingProvider
{
    public function appKey(): string;

    public function bindingKey(): string;

    public function bindingVersion(): string;

    public function capabilityContractVersion(): int;

    public function resolvePreview(SignatureBulkBindingPreviewContext $context): SignatureBulkBindingPreviewResult;

    public function reauthorize(SignatureBulkBindingExecutionContext $context): SignatureBulkBindingReauthorizationResult;
}
