<?php

declare(strict_types=1);

use Nexia\Fixture\FixtureContext;
use Nexia\Fixture\FixtureLegalEntity;
use Nexia\Fixture\FixtureReference;
use Nexia\Fixture\FixtureSubject;

require dirname(__DIR__).'/vendor/autoload.php';

$context = new FixtureContext(
    fixtureKey: 'example',
    legalEntities: [new FixtureLegalEntity(10, 'EXAMPLE', 'Example Entity')],
    subjects: [new FixtureSubject('person@example.test', 1, 20, 'user-public', 30, 'party-public', 'Example Person')],
    actorUserId: 20,
    actorPublicId: 'user-public',
);
$reference = new FixtureReference('example.record', 'person@example.test', '40', 'record-public');
$context->publish($reference);

if ($context->legalEntity('EXAMPLE')?->id !== 10
    || $context->subject('person@example.test')?->partyPublicId !== 'party-public'
    || $context->reference('example.record', 'person@example.test') !== $reference
) {
    throw new RuntimeException('Demo data context identity or reference exchange changed unexpectedly.');
}

echo "Demo data contracts passed.\n";
