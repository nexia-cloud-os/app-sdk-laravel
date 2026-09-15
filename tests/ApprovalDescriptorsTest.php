<?php

declare(strict_types=1);

use Nexia\AppDescriptors\Contracts\AppDescriptorContribution;
use Nexia\AppDescriptors\AppDescriptorSet;
use Nexia\AppDescriptors\ApprovalDocumentSchema;
use Nexia\AppDescriptors\ApprovalFormBindingDescriptor;

require dirname(__DIR__).'/vendor/autoload.php';

$schema = new ApprovalDocumentSchema(
    appKey: 'sample',
    resourceKey: 'expense_report',
    sections: [[
        'fields' => [
            ['key' => 'title', 'label_key' => 'sample.report.title'],
            [
                'key' => 'lines',
                'type' => 'table',
                'columns' => [[
                    'key' => 'amount',
                    'label_key' => 'sample.report.amount',
                    'type' => 'currency',
                ]],
            ],
        ],
    ]],
);
$binding = new ApprovalFormBindingDescriptor(
    appKey: 'sample',
    resourceKey: 'expense_report',
    actionKey: 'submit',
    labelKey: 'sample.report.submit',
    entryModes: ['create'],
    formWidgetKey: 'sample.expense_report.form',
    documentSchemaKey: $schema->key,
);

$descriptors = new class implements AppDescriptorContribution
{
    public static AppDescriptorSet $descriptors;

    public static function appDescriptors(): AppDescriptorSet
    {
        return self::$descriptors;
    }
};
$descriptors::$descriptors = AppDescriptorSet::of($binding, $schema);

if ($schema->key !== 'sample.expense_report'
    || $binding->key !== 'sample.expense_report.submit'
    || ! $binding->supportsEntryMode('create')
    || $binding->supportsEntryMode('link')
    || $binding->submitPermission() !== $binding->key
    || $descriptors::appDescriptors()->ofType(ApprovalFormBindingDescriptor::class) !== [$binding]
    || $descriptors::appDescriptors()->ofType(ApprovalDocumentSchema::class) !== [$schema]
) {
    throw new RuntimeException('Approval descriptor contracts changed unexpectedly.');
}

try {
    new ApprovalDocumentSchema(
        appKey: 'sample',
        resourceKey: 'invalid',
        sections: [['fields' => [['key' => 'bad', 'type' => 'unsupported']]]],
    );
    throw new RuntimeException('Unsupported Approval document field types must be rejected.');
} catch (InvalidArgumentException) {
    // Expected contract validation.
}

fwrite(STDOUT, "Approval descriptor contracts are valid.\n");
