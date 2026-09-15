<?php

declare(strict_types=1);

namespace Nexia\AppDescriptors;

use InvalidArgumentException;

/**
 * Immutable, ordered collection of App-published descriptors.
 *
 * A descriptor key is unique only within its concrete descriptor type. This
 * permits deliberately related descriptors, such as an Approval binding and
 * an Approval document schema, to share their stable business identity.
 */
final readonly class AppDescriptorSet
{
    /** @var list<AppDescriptor> */
    private array $descriptors;

    /** @param list<AppDescriptor> $descriptors */
    private function __construct(array $descriptors)
    {
        $seen = [];

        foreach ($descriptors as $descriptor) {
            $class = $descriptor::class;
            $key = $descriptor->descriptorKey();

            if ($key === '' || $key !== trim($key)) {
                throw new InvalidArgumentException(
                    "App descriptor [{$class}] must declare a non-empty, trimmed descriptor key.",
                );
            }

            $identity = $class."\0".$key;

            if (isset($seen[$identity])) {
                throw new InvalidArgumentException(
                    "App descriptor [{$class}:{$key}] is declared more than once.",
                );
            }

            $seen[$identity] = true;
        }

        $this->descriptors = $descriptors;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function of(AppDescriptor ...$descriptors): self
    {
        return new self($descriptors);
    }

    /** @param iterable<mixed> $descriptors */
    public static function from(iterable $descriptors): self
    {
        $entries = [];

        foreach ($descriptors as $descriptor) {
            if (! $descriptor instanceof AppDescriptor) {
                throw new InvalidArgumentException(
                    'An App descriptor set may contain only '.AppDescriptor::class.' instances.',
                );
            }

            $entries[] = $descriptor;
        }

        return new self($entries);
    }

    /** @return list<AppDescriptor> */
    public function all(): array
    {
        return $this->descriptors;
    }

    /**
     * @template T of AppDescriptor
     *
     * @param class-string<T> $descriptorClass
     * @return list<T>
     */
    public function ofType(string $descriptorClass): array
    {
        if (! is_a($descriptorClass, AppDescriptor::class, true)) {
            throw new InvalidArgumentException(
                "App descriptor type [{$descriptorClass}] must implement ".AppDescriptor::class.'.',
            );
        }

        return array_values(array_filter(
            $this->descriptors,
            static fn (AppDescriptor $descriptor): bool => $descriptor instanceof $descriptorClass,
        ));
    }
}
