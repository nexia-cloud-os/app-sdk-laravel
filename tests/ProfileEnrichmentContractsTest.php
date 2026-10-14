<?php

declare(strict_types=1);

use Nexia\Identity\PersonProfileView;
use Nexia\Organization\LegalEntityProfile;

require __DIR__.'/register-source-autoload.php';

$legacy = new PersonProfileView('person', 'Name', null, [], null, null, null, 'v1');
assert($legacy->birthDate === null);
$current = new PersonProfileView(...[...get_object_vars($legacy), 'birthDate' => '1990-04-12']);
assert((new PersonProfileView(...get_object_vars($current)))->birthDate === '1990-04-12');

$profile = new LegalEntityProfile('Company', '123-45-67890', 'Representative', ['line1' => 'Address']);
assert(get_object_vars(new LegalEntityProfile(...get_object_vars($profile))) === get_object_vars($profile));

echo "Profile enrichment contracts passed.\n";
