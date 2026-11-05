<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Nexia\Laravel\ResourceComposition\Concerns\ReportsNullableLegalEntityData;
use Nexia\Laravel\ResourceComposition\Concerns\ReportsTenantCommonData;
use Nexia\Laravel\ResourceComposition\Contracts\DashboardReportLegalEntityFilter;
use Nexia\Laravel\ResourceComposition\Contracts\DashboardReportFilterDependencies;
use Nexia\Laravel\ResourceComposition\Contracts\ReportLegalEntityFilter;

require dirname(__DIR__).'/vendor/autoload.php';

$database = new Manager;
$database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
$database->setAsGlobal();
$database->bootEloquent();
$database->getConnection()->statement('create table dashboard_filter_test (id integer primary key, legal_entity_id integer)');
$database->getConnection()->table('dashboard_filter_test')->insert([
    ['id' => 1, 'legal_entity_id' => null], ['id' => 2, 'legal_entity_id' => 1],
    ['id' => 3, 'legal_entity_id' => 2], ['id' => 4, 'legal_entity_id' => 3],
]);
$reportOnly = new class extends Model implements ReportLegalEntityFilter {
    use ReportsTenantCommonData;
    protected $table = 'dashboard_filter_test';
};
$common = new class extends Model implements DashboardReportLegalEntityFilter {
    use ReportsTenantCommonData;
    protected $table = 'dashboard_filter_test';
};
$applicable = new class extends Model implements DashboardReportLegalEntityFilter {
    use ReportsNullableLegalEntityData;
    protected $table = 'dashboard_filter_test';
};
$dependent = new class extends Model implements DashboardReportFilterDependencies {
    use ReportsTenantCommonData;
    protected $table = 'dashboard_filter_test';
    public static function dashboardFilterPermissions(): array { return ['sample.first.read', 'sample.second.read']; }
};
$actor = new User;
assert($reportOnly instanceof ReportLegalEntityFilter && ! $reportOnly instanceof DashboardReportLegalEntityFilter);
assert($common instanceof ReportLegalEntityFilter && $applicable instanceof ReportLegalEntityFilter);
assert(! $common instanceof DashboardReportFilterDependencies);
assert($dependent instanceof DashboardReportLegalEntityFilter && $dependent::dashboardFilterPermissions() === ['sample.first.read', 'sample.second.read']);
assert($common->scopeReportLegalEntities($common->newQuery(), [1, 2], $actor)->orderBy('id')->pluck('id')->all() === [1, 2, 3, 4]);
assert($applicable->scopeReportLegalEntities($applicable->newQuery(), [1, 2], $actor)->orderBy('id')->pluck('id')->all() === [1, 2, 3]);
assert($applicable->scopeReportLegalEntities($applicable->newQuery(), [2], $actor)->orderBy('id')->pluck('id')->all() === [1, 3]);
assert($common->scopeReportLegalEntities($common->newQuery(), [], $actor)->count() === 0);
assert($applicable->scopeReportLegalEntities($applicable->newQuery(), [], $actor)->count() === 0);
echo "Dashboard report filter opt-in contract passed.\n";
