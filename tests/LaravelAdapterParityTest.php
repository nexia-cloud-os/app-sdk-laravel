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
try {
    $fixture->media();
    throw new RuntimeException('An isolated adapter exposed a local media relation.');
} catch (LogicException $exception) {
    assert(str_contains($exception->getMessage(), 'Local media is unavailable'));
}

$database = new Illuminate\Database\Capsule\Manager;
$database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
$database->setEventDispatcher(new Illuminate\Events\Dispatcher(new Illuminate\Container\Container));
$database->setAsGlobal();
$database->bootEloquent();
Illuminate\Container\Container::getInstance()->instance(
    Nexia\Laravel\Database\Contracts\AppDatabaseConnections::class,
    new class($database) implements Nexia\Laravel\Database\Contracts\AppDatabaseConnections {
        public function __construct(private Illuminate\Database\Capsule\Manager $database) {}

        public function connection(string $appKey): Illuminate\Database\Connection
        {
            return $this->database->getConnection();
        }

        public function modelConnectionName(string $modelClass): ?string
        {
            return 'default';
        }
    },
);
Nexia\Laravel\Database\AppDatabaseConnectionsResolver::configure(
    static fn (): Nexia\Laravel\Database\Contracts\AppDatabaseConnections => Illuminate\Container\Container::getInstance()->make(Nexia\Laravel\Database\Contracts\AppDatabaseConnections::class),
);
$database->schema()->create('app_rows', function (Illuminate\Database\Schema\Blueprint $table): void {
    $table->id();
});
$row = new class extends Nexia\Laravel\Models\NexiaModel {
    protected $table = 'app_rows';

    public $timestamps = false;
};
$row->save();
assert($row->delete());
assert($database->table('app_rows')->count() === 0);
assert(! $database->schema()->hasTable('media'));

echo "Laravel adapter behavior parity passed.\n";
