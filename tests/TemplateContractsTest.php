<?php

declare(strict_types=1);

use Nexia\Templates\CopyableTemplate;
use Nexia\Templates\TemplateApplicationResult;

require dirname(__DIR__).'/vendor/autoload.php';

$template = new CopyableTemplate(
    app: 'sample-payments',
    key: 'smb-baseline',
    version: '1',
    scope: CopyableTemplate::SCOPE_TENANT,
    source: ['element' => 'base-pay'],
);
$result = new TemplateApplicationResult([
    ['type' => 'sample-payments.element', 'id' => 'element-1', 'key' => 'base-pay', 'version' => 1],
]);

if ($template->scope !== 'tenant'
    || $template->source !== ['element' => 'base-pay']
    || $result->resourceRefs[0]['id'] !== 'element-1'
) {
    throw new RuntimeException('Template contracts changed unexpectedly.');
}

try {
    new CopyableTemplate('sample-payments', 'invalid', '1', 'organization', []);
    throw new RuntimeException('Unsupported template scope was accepted.');
} catch (InvalidArgumentException) {
    // Expected.
}

fwrite(STDOUT, "Template contracts passed.\n");
