<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Nexia\Laravel\Database\AppDatabaseConnectionsResolver;
use Nexia\Laravel\Database\Contracts\AppDatabaseConnections;
use Nexia\Laravel\Database\Facades\AppDatabase;
use Nexia\Laravel\Models\Concerns\UsesAppDatabaseConnection;
use Nexia\Laravel\Models\NexiaModel;

require __DIR__.'/../vendor/autoload.php';

final class AppDatabaseContractFixtureModel extends Model
{
    use UsesAppDatabaseConnection;
}

final class AppDatabaseContractNexiaModel extends NexiaModel {}

final class AppDatabaseContractHostModel extends NexiaModel
{
    protected $connection = 'host_connection';
}

final class AppDatabaseContractFixture extends AppDatabase
{
    public const APP_KEY = 'fixture';
}

$connection = new class extends Connection {
    public function __construct() {}
};
$container = new Container;
$container->instance(AppDatabaseConnections::class, new class($connection) implements AppDatabaseConnections
{
    public function __construct(private Connection $connection) {}

    public function connection(string $appKey): Connection
    {
        assert($appKey === 'fixture');

        return $this->connection;
    }

    public function modelConnectionName(string $modelClass): ?string
    {
        if ($modelClass === AppDatabaseContractHostModel::class) {
            return null;
        }
        assert(in_array($modelClass, [AppDatabaseContractFixtureModel::class, AppDatabaseContractNexiaModel::class], true));

        return 'app_fixture';
    }
});
try {
    AppDatabaseContractFixture::connection();
    throw new RuntimeException('Unconfigured host database access was allowed.');
} catch (LogicException $exception) {
    assert(str_contains($exception->getMessage(), 'not been configured'));
}
AppDatabaseConnectionsResolver::configure(static fn (): AppDatabaseConnections => $container->make(AppDatabaseConnections::class));

assert(AppDatabaseContractFixture::connection() === $connection);
assert((new AppDatabaseContractFixtureModel)->getConnectionName() === 'app_fixture');
assert((new AppDatabaseContractNexiaModel)->getConnectionName() === 'app_fixture');
assert((new AppDatabaseContractNexiaModel)->setConnection('foreign')->getConnectionName() === 'app_fixture');
assert((new AppDatabaseContractHostModel)->getConnectionName() === 'host_connection');
assert((new AppDatabaseContractHostModel)->setConnection('other_host')->getConnectionName() === 'other_host');
try {
    AppDatabaseContractFixture::connection('tenant');
    throw new RuntimeException('An App facade selected a foreign connection.');
} catch (LogicException) {
}

$original = $container->make(AppDatabaseConnections::class);
$container->instance(AppDatabaseConnections::class, clone $original);
assert(AppDatabaseConnectionsResolver::resolve() !== $original);

echo "App database connection contracts are valid.\n";
