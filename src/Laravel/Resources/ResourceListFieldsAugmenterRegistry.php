<?php

declare(strict_types=1);

namespace Nexia\Laravel\Resources;

use Closure;
use Illuminate\Database\Eloquent\Model;

/** SDK-wide host configuration for platform list capabilities. */
final class ResourceListFieldsAugmenterRegistry
{
    /** @var (Closure(ResourceListFields, Model): void)|null */
    private static ?Closure $augmenter = null;

    /** @param (Closure(ResourceListFields, Model): void)|null $augmenter */
    public static function configure(?Closure $augmenter): void
    {
        self::$augmenter = $augmenter;
    }

    public static function augment(ResourceListFields $fields, Model $model): void
    {
        if (self::$augmenter !== null) {
            (self::$augmenter)($fields, $model);
        }
    }
}
