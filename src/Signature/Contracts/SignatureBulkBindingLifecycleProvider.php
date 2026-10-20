<?php

declare(strict_types=1);

namespace Nexia\Signature\Contracts;

use Nexia\Signature\SignatureBulkBindingLifecycleContext;
use Nexia\Signature\SignatureBulkBindingReauthorizationResult;

/** Idempotent App-owned subject reservation and committed request reconciliation. */
interface SignatureBulkBindingLifecycleProvider extends SignatureBulkBindingProvider
{
    public function reconcile(SignatureBulkBindingLifecycleContext $context): SignatureBulkBindingReauthorizationResult;
}
