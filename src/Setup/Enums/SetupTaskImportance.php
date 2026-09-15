<?php

declare(strict_types=1);

namespace Nexia\Setup\Enums;

/** Declares whether a task is required or recommended by its owning App. */
enum SetupTaskImportance: string
{
    case Required = 'required';
    case Recommended = 'recommended';
}
