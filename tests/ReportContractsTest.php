<?php

declare(strict_types=1);

use Nexia\Reports\ReportDefinition;

require dirname(__DIR__).'/vendor/autoload.php';

$report = ReportDefinition::count('sample.records', 'sample.reports.records', 'sample.record');
assert($report->composition->sources === [['alias' => 'record', 'resource_key' => 'sample.record']]);
assert($report->composition->measures[0]['operation'] === 'count');
assert($report->presentation['channels']['value'] === 'records');
assert(ReportDefinition::fromArray($report->toArray())->toArray() === $report->toArray());
foreach (['records', 'Sample.records', 'sample records'] as $key) {
    try {
        ReportDefinition::count($key, 'sample.reports.records', 'sample.record');
        throw new RuntimeException('Invalid report key accepted.');
    } catch (InvalidArgumentException) {
    }
}
$database = new \Illuminate\Database\Capsule\Manager;
$database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
$database->setAsGlobal();
$database->bootEloquent();
$database->getConnection()->statement('create table report_scope_test (id integer primary key, legal_entity_id integer)');
$database->getConnection()->table('report_scope_test')->insert([
    ['id' => 1, 'legal_entity_id' => null], ['id' => 2, 'legal_entity_id' => 1],
    ['id' => 3, 'legal_entity_id' => 2], ['id' => 4, 'legal_entity_id' => 3],
]);
$actor = new \Illuminate\Foundation\Auth\User;
$common = new class extends \Illuminate\Database\Eloquent\Model {
    use \Nexia\Laravel\ResourceComposition\Concerns\ReportsTenantCommonData;
    protected $table = 'report_scope_test';
};
$applicable = new class extends \Illuminate\Database\Eloquent\Model {
    use \Nexia\Laravel\ResourceComposition\Concerns\ReportsNullableLegalEntityData;
    protected $table = 'report_scope_test';
};
assert($common->scopeReportLegalEntities($common->newQuery(), [1, 2], $actor)->count() === 4);
assert($common->scopeReportLegalEntities($common->newQuery(), [], $actor)->count() === 0);
assert($applicable->scopeReportLegalEntities($applicable->newQuery(), [1, 2], $actor)->orderBy('id')->pluck('id')->all() === [1, 2, 3]);
assert($applicable->scopeReportLegalEntities($applicable->newQuery(), [], $actor)->count() === 0);
echo "Report contracts passed.\n";
