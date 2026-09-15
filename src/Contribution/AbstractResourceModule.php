<?php

declare(strict_types=1);

namespace Nexia\Contribution;

use LogicException;
use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;
use Nexia\AppDescriptors\Contracts\AppDescriptorSet;

/**
 * Canonical static descriptor surface for one Resource Module.
 *
 * Concrete modules keep their navigation, permission, authorization, model,
 * and shell declarations on the module itself. This base publishes only the
 * optional public Resource descriptor carried by its module definition.
 */
abstract class AbstractResourceModule implements AppDescriptorContribution
{
    abstract public static function resourceModuleDefinition(): ResourceModuleDefinition;

    final public static function appDescriptors(): AppDescriptorSet
    {
        $definition = static::resourceModuleDefinition();

        if (is_subclass_of(static::class, ResourceCatalogContribution::class)
            && static::resourceKey() !== $definition->resourceKey) {
            throw new LogicException(sprintf(
                'Resource Module [%s] catalog key [%s] does not match definition key [%s].',
                static::class,
                static::resourceKey(),
                $definition->resourceKey,
            ));
        }

        $descriptor = $definition->publicDescriptor();

        return $descriptor === null
            ? AppDescriptorSet::empty()
            : AppDescriptorSet::of($descriptor);
    }
}
