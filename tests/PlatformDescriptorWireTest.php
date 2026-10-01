<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Nexia\AppDescriptors\PlatformDescriptorWire;
use Nexia\AppDescriptors\ProcessStartBindingDescriptor;
use Nexia\AppDescriptors\ApprovalDocumentSchema;
use Nexia\AppDescriptors\ApprovalRoutePolicyPresetDescriptor;
use Nexia\Approval\Domain\ApprovalRoutePolicyStep;

foreach ([
    new \Nexia\AppDescriptors\SlotWidgetDescriptor('sample.record.submit', '1.0', 'approval.composer.business_form', 'sample.record.submit', 1),
    new \Nexia\AppDescriptors\SlotWidgetDescriptor('sample.self.profile', '1.0', 'profile.self.overview', 'SampleProfile', 2, permission: 'sample.profile.read', familyKey: 'sample.self'),
    new \Nexia\AppDescriptors\DecisionResultTemplateDescriptor('sample.decision', '1.0', 'sample.decision.label',
        [new \Nexia\AppDescriptors\DecisionResultFieldDescriptor('approved', 'sample.approved', 'boolean', 'plain')]),
    new \Nexia\AppDescriptors\OfficialSealUseDescriptor('sample', 'sample.document.issued', ['sample.document.issue']),
    new ProcessStartBindingDescriptor('sample', 'start', 'sample.process', 'process', 'sample.record', 'sample.record.start', 'sample.start'),
    new \Nexia\AppDescriptors\ProcessWorkActionDescriptor('serviceTask', 'sample', 'finish', 'sample.finish', 'sample.finish'),
    new \Nexia\AppDescriptors\ProcessUserTaskFormDescriptor('sample.form', 'sample', 'sample.form.title',
        rendering: ['mode' => 'slot_widget', 'slot' => \Nexia\AppDescriptors\ProcessUserTaskFormDescriptor::FORM_SLOT, 'component' => 'SampleForm', 'slot_api_version' => 1], submissionActionKey: 'submit'),
    new \Nexia\AppDescriptors\SignatureDocumentDataSourceDescriptor(
        'sample', 'sample.document', 1, ['sample.record'], \Nexia\Signature\SignatureDocumentDataCardinality::One,
        \Nexia\Signature\SignatureDocumentDataLookupMode::DerivedRef, [],
        [new \Nexia\AppDescriptors\SignatureDocumentDataFieldDescriptor('name', \Nexia\Signature\SignatureDocumentDataFieldType::String,
            true, \Nexia\Signature\SignatureDataClassification::Confidential, [\Nexia\Signature\SignatureDocumentDataFormatter::Plain], 'sample.name')],
        ['name' => 'Synthetic'], 'sample.document.title', 'sample.document.description', defaultSourceRefAnchor: 'record',
    ),
    new ApprovalDocumentSchema('sample', 'record', [['fields' => [['key' => 'name', 'label_key' => 'sample.name']]]]),
    new ApprovalRoutePolicyPresetDescriptor('sample', 'route', 'sample.route', [new ApprovalRoutePolicyStep('fixed_users', ['users' => [1]])]),
] as $descriptor) {
    $wire = json_decode(json_encode(PlatformDescriptorWire::encode($descriptor), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    assert(PlatformDescriptorWire::encode(PlatformDescriptorWire::decode($wire, 'sample')) === $wire);
    foreach ([['wire' => $wire, 'app' => 'foreign'], ['wire' => [...$wire, 'type' => 'Some\\Class'], 'app' => 'sample'],
        ['wire' => [...$wire, 'extra' => true], 'app' => 'sample'], ['wire' => [...$wire, 'data' => [...$wire['data'], 'unexpected' => true]], 'app' => 'sample']] as $bad) {
        $rejected = false;
        try { PlatformDescriptorWire::decode($bad['wire'], $bad['app']); } catch (Throwable) { $rejected = true; }
        assert($rejected);
    }
}
echo "Platform descriptor boundary passed.\n";

foreach ([['foreign.issue'], [], ['sample.issue', 'sample.issue'], ['sample.issue*'], [null]] as $permissions) {
    $rejected = false;
    try { new \Nexia\AppDescriptors\OfficialSealUseDescriptor('sample', 'sample.document.issued', $permissions); }
    catch (InvalidArgumentException) { $rejected = true; }
    assert($rejected);
}
