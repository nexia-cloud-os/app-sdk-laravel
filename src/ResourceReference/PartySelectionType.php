<?php

declare(strict_types=1);

namespace Nexia\ResourceReference;

enum PartySelectionType: string
{
    case Person = 'person';
    case Organization = 'organization';
}
