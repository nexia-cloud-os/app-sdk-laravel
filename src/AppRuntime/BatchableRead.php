<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use Attribute;

/**
 * Opt a controller method into bounded JSON GET batching.
 * The handler must be read-only and retain its ordinary authorization checks.
 * This is an optimization hint, never permission to execute or skip middleware.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class BatchableRead {}
