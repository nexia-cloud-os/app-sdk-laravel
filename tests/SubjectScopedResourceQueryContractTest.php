<?php

declare(strict_types=1);

require dirname(__DIR__).'/src/Contribution/Contracts/ResourceCatalogContribution.php';
require dirname(__DIR__).'/src/Contribution/Contracts/ResourceAuthorizationContribution.php';
require dirname(__DIR__).'/src/Contribution/Contracts/SubjectScopedResourceQuery.php';

use Nexia\Contribution\Contracts\ResourceAuthorizationContribution;
use Nexia\Contribution\Contracts\SubjectScopedResourceQuery;

assert(is_subclass_of(SubjectScopedResourceQuery::class, ResourceAuthorizationContribution::class));
$method = new ReflectionMethod(SubjectScopedResourceQuery::class, 'scopeSubjects');
assert($method->isPublic() && $method->isStatic());
assert(array_map(fn (ReflectionParameter $parameter) => (string) $parameter->getType(), $method->getParameters()) === [
    'Illuminate\\Database\\Eloquent\\Builder',
    'Illuminate\\Contracts\\Auth\\Authenticatable',
    'Nexia\\Organization\\OrganizationTarget',
    'array',
]);
assert((string) $method->getReturnType() === 'Illuminate\\Database\\Eloquent\\Builder');
