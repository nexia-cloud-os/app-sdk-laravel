<?php

declare(strict_types=1);

$apps = [
    ['assets', 'amuzcorp/nexia-assets', 'Amuzcorp\\Nexia\\AssetManagement\\'],
    ['budget-controlling', 'amuzcorp/nexia-budget-controlling', 'Amuzcorp\\Nexia\\BudgetControlling\\'],
    ['demand-planning', 'amuzcorp/nexia-demand-planning', 'Amuzcorp\\Nexia\\DemandPlanning\\'],
    ['expenses', 'amuzcorp/nexia-expenses', 'Amuzcorp\\Nexia\\Expenses\\'],
    ['grants', 'amuzcorp/nexia-grants', 'Amuzcorp\\Nexia\\GrantsManagement\\'],
    ['inventory', 'amuzcorp/nexia-inventory', 'Amuzcorp\\Nexia\\Inventory\\'],
    ['manufacturing', 'amuzcorp/nexia-manufacturing', 'Amuzcorp\\Nexia\\Manufacturing\\'],
    ['order-management', 'amuzcorp/nexia-order-management', 'Amuzcorp\\Nexia\\OrderManagement\\'],
    ['payables', 'amuzcorp/nexia-payables', 'Amuzcorp\\Nexia\\Payables\\'],
    ['payroll', 'amuzcorp/nexia-payroll', 'Amuzcorp\\Nexia\\Payroll\\'],
    ['people', 'amuzcorp/nexia-people', 'Amuzcorp\\Nexia\\PeopleCore\\'],
    ['procurement', 'amuzcorp/nexia-procurement', 'Amuzcorp\\Nexia\\Procurement\\'],
    ['product', 'amuzcorp/nexia-product', 'Amuzcorp\\Nexia\\Product\\'],
    ['product-engineering', 'amuzcorp/nexia-product-engineering', 'Amuzcorp\\Nexia\\ProductEngineering\\'],
    ['psa', 'amuzcorp/nexia-psa', 'Amuzcorp\\Nexia\\ProfessionalServices\\'],
    ['quality', 'amuzcorp/nexia-quality', 'Amuzcorp\\Nexia\\Quality\\'],
    ['receivables', 'amuzcorp/nexia-receivables', 'Amuzcorp\\Nexia\\Receivables\\'],
    ['recruiting', 'amuzcorp/nexia-recruiting', 'Amuzcorp\\Nexia\\Recruiting\\'],
    ['sales', 'amuzcorp/nexia-sales', 'Amuzcorp\\Nexia\\Sales\\'],
    ['supply-planning', 'amuzcorp/nexia-supply-planning', 'Amuzcorp\\Nexia\\SupplyPlanning\\'],
    ['talent', 'amuzcorp/nexia-talent', 'Amuzcorp\\Nexia\\Talent\\'],
    ['time-absence', 'amuzcorp/nexia-time-absence', 'Amuzcorp\\Nexia\\TimeAbsence\\'],
    ['treasury', 'amuzcorp/nexia-treasury', 'Amuzcorp\\Nexia\\Treasury\\'],
    ['warehouse', 'amuzcorp/nexia-warehouse', 'Amuzcorp\\Nexia\\Warehouse\\'],
];
$roots = [dirname(__DIR__).'/src'];
$extensions = ['php'];
$violations = [];

foreach ($roots as $root) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo
            || ! $file->isFile()
            || ! in_array($file->getExtension(), $extensions, true)) {
            continue;
        }

        $source = file_get_contents($file->getPathname());
        if ($source === false) {
            throw new RuntimeException("Unable to read SDK production file [{$file->getPathname()}].");
        }

        foreach ($apps as [$key, $package, $namespace]) {
            $needles = [
                $package,
                $namespace,
                str_replace('\\', '\\\\', $namespace),
                '/apps/'.$key,
                '/api/'.$key,
                '/'.$key.'/',
            ];
            foreach (["'", '"', '`'] as $quote) {
                $needles[] = $quote.$key.$quote;
                $needles[] = $quote.$key.'.';
                $needles[] = $quote.$key.'::';
            }

            foreach ($needles as $needle) {
                // File inventory is a platform lifecycle operation, unrelated to the Inventory App.
                if ($file->getPathname() === dirname(__DIR__).'/src/Attachments/FileLifecycleEvent.php'
                    && $key === 'inventory'
                    && in_array($needle, ["'inventory'", '"inventory"'], true)) {
                    continue;
                }

                if (str_contains($source, $needle)) {
                    $violations[] = str_replace(dirname(__DIR__).'/', '', $file->getPathname()).' -> '.$key;
                    break 2;
                }
            }
        }
    }
}

if ($violations !== []) {
    throw new RuntimeException('SDK production source names installed Apps: '.implode(', ', $violations));
}

echo "App-neutrality boundary passed.\n";
