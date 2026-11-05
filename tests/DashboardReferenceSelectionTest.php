<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Illuminate\Http\Request;
use Nexia\Laravel\Dashboard\DashboardReferenceSelection;
use Nexia\Identity\Contracts\Actor;
use Nexia\Organization\Contracts\LegalEntity;
use Nexia\ResourceReference\ResourceReferenceResolutionContext;

$actor = new class implements Actor {
    public function key(): int|string { return 1; }
    public function publicId(): string { return 'actor'; }
    public function displayLabel(): string { return 'Actor'; }
    public function partyKey(): int|string|null { return 1; }
    public function isVerified(): bool { return true; }
};
$entity = new class implements LegalEntity {
    public function key(): int|string { return 1; }
    public function publicId(): string { return 'entity'; }
    public function displayLabel(): string { return 'Entity'; }
    public function code(): string { return 'E'; }
    public function partyPublicId(): ?string { return null; }
    public function isActiveOrganization(): bool { return true; }
};
$context = new ResourceReferenceResolutionContext($entity, $actor, new DateTimeImmutable, 'talent.manager');
$request = Request::create('/', 'GET', content: '{"method":"searchPage"}');
$request->headers->set('Content-Type', 'application/json');
$selection = ['dashboard_page'=>true,'population'=>'direct_reports','resource_key'=>'people.worker_assignment',
    'purpose'=>'talent.manager','actor_public_id'=>'actor','legal_entity_public_id'=>'entity'];
$allows = static function (array $input) use ($request, $context): bool {
    $request->attributes->set('nexia.runtime.reference_scope',['widget_population_reference'=>$input]);
    return DashboardReferenceSelection::isDirectReportPage($request,$context,'people.worker_assignment','talent.manager');
};
if (! $allows($selection)) throw new RuntimeException('Valid direct report page denied.');
foreach ([['dashboard_page'=>false],['population'=>'legal_entity'],['resource_key'=>'other'],['purpose'=>'other'],
    ['actor_public_id'=>'other'],['legal_entity_public_id'=>'other']] as $wrong) {
    if ($allows(array_replace($selection,$wrong))) throw new RuntimeException('Mismatched direct report authority accepted.');
}
$request->attributes->remove('nexia.runtime.reference_scope');
if (DashboardReferenceSelection::isDirectReportPage($request,$context,'people.worker_assignment','talent.manager')) throw new RuntimeException('Missing authority accepted.');
echo "Dashboard direct-report reference scope passed.\n";
