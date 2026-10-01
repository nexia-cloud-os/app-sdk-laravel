<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use Nexia\Actions\ActionDefinition;

// Compatibility name; both names resolve to the same Action declaration.
class_alias(ActionDefinition::class, __NAMESPACE__.'\\ResourceActionDescriptor');
