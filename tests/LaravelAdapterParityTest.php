<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Nexia\Laravel\Models\NexiaEntityModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

final class LaravelAdapterParityFixture extends NexiaEntityModel {}

$fixture = (new ReflectionClass(LaravelAdapterParityFixture::class))->newInstanceWithoutConstructor();
$traits = class_uses_recursive(LaravelAdapterParityFixture::class);

assert(in_array(SoftDeletes::class, $traits, true));
assert($fixture->getDeletedAtColumn() === 'deleted_at');
assert(in_array(LogsActivity::class, $traits, true));
assert($fixture->getActivitylogOptions() instanceof LogOptions);
assert(in_array(Searchable::class, $traits, true));
assert(method_exists(LaravelAdapterParityFixture::class, 'search'));
assert($fixture instanceof HasMedia);
assert(method_exists(LaravelAdapterParityFixture::class, 'media'));

echo "Laravel adapter behavior parity passed.\n";
