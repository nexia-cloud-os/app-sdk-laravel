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
echo "Report contracts passed.\n";
