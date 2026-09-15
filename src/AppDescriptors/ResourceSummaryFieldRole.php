<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

/** Semantic placement hint for compact Resource Summary consumers. */
enum ResourceSummaryFieldRole: string
{
    case Subtitle = 'subtitle';
    case Meta = 'meta';
    case Status = 'status';
}
