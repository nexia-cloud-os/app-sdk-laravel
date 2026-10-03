<?php

declare(strict_types=1);

namespace Nexia\AsyncWork\Contracts;

use Nexia\AsyncWork\AppWorkSchedule;

/** Declares App-owned work that Core schedules centrally for eligible installations. */
interface AppWorkScheduleContribution
{
    /** @return list<AppWorkSchedule> */
    public static function appWorkSchedules(): array;
}
