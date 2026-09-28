<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Nexia\AppDescriptors\PlatformDescriptorWire;
use Nexia\AppDescriptors\ProcessStartBindingDescriptor;
use Nexia\AppDescriptors\ApprovalDocumentSchema;
use Nexia\AppDescriptors\ApprovalRoutePolicyPresetDescriptor;
use Nexia\Approval\Domain\ApprovalRoutePolicyStep;

foreach ([
    new ProcessStartBindingDescriptor('sample', 'start', 'sample.process', 'process', 'sample.record', 'sample.record.start', 'sample.start'),
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
