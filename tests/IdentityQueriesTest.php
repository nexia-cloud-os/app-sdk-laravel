<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\ActorDirectory;
use Nexia\Identity\Contracts\BatchActorDirectory;
use Nexia\Identity\Contracts\BatchCanonicalPartyDirectory;
use Nexia\Identity\Contracts\BatchPartyDirectory;
use Nexia\Identity\Contracts\BatchPersonProfileDirectory;
use Nexia\Identity\Contracts\Party;
use Nexia\Identity\Contracts\PartyBusinessDirectory;
use Nexia\Identity\Contracts\PartyRelationshipDirectory;
use Nexia\Identity\Contracts\PersonProfileDirectory;
use Nexia\Identity\IdentityQueries;
use Nexia\Identity\PartyRelationship;
use Nexia\Identity\PersonDirectoryEntry;
use Nexia\Identity\PersonProfileView;
use Nexia\Identity\Query\IdentityQuery;
use Nexia\Laravel\Identity\Contracts\PartyDirectory;

require dirname(__DIR__).'/vendor/autoload.php';

function check(bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); }

function identityQueries(
    ?PartyDirectory $party = null,
    ?ActorDirectory $user = null,
    ?PersonProfileDirectory $profile = null,
    ?PartyBusinessDirectory $business = null,
    ?PartyRelationshipDirectory $relationship = null,
): IdentityQueries {
    $container = new Container;
    foreach ([PartyDirectory::class => $party, ActorDirectory::class => $user, PersonProfileDirectory::class => $profile,
        PartyBusinessDirectory::class => $business, PartyRelationshipDirectory::class => $relationship] as $contract => $value) {
        if ($value !== null) $container->instance($contract, $value);
    }
    return new IdentityQueries($container);
}

final readonly class SampleIdentity implements Party, Actor
{
    public function __construct(private int $id) {}
    public function key(): int|string { return $this->id; }
    public function publicId(): string { return 'public-'.$this->id; }
    public function displayLabel(): string { return 'name-'.$this->id; }
    public function isPerson(): bool { return true; }
    public function isOrganization(): bool { return false; }
    public function isArchived(): bool { return false; }
    public function partyKey(): int|string|null { return $this->id; }
    public function isVerified(): bool { return true; }
}

class SampleParties implements PartyDirectory, BatchPartyDirectory
{
    public array $calls = [];
    public array $canonicalInputs = [];
    public ?Exception $canonicalFailure = null;
    private function party(int|string $key): ?Party { return in_array((string) $key, ['1', '2'], true) ? new SampleIdentity((int) $key) : null; }
    public function findByKey(int|string $key): ?Party { $this->calls[] = 'key'; return $this->party($key); }
    public function findByKeys(array $keys): array { $this->calls[] = 'keys'; return array_values(array_filter(array_map(fn ($key) => $this->party($key), array_unique($keys)))); }
    public function findByPublicId(string $id): ?Party { $this->calls[] = 'public_id'; return str_starts_with($id, 'public-') ? $this->party(substr($id, 7)) : null; }
    public function findByPublicIds(array $ids): array { $this->calls[] = 'public_ids'; $result=[]; foreach (array_unique($ids) as $id) if ($party=$this->party(substr($id, 7))) $result[strtolower($id)]=$party; return $result; }
    public function requireByKey(int|string $key): Party { return $this->findByKey($key) ?? throw new ModelNotFoundException; }
    public function requireByPublicId(string $id): Party { return $this->findByPublicId($id) ?? throw new ModelNotFoundException; }
    public function findCanonicalByPublicId(string $id): ?Party { $this->calls[] = 'canonical'; $this->canonicalInputs[] = $id; if ($this->canonicalFailure !== null) throw $this->canonicalFailure; return in_array($id, ['merged', 'merged-again'], true) ? $this->party(2) : null; }
    public function exists(string $id): bool { $this->calls[] = 'exists'; return $id === 'public-1'; }
    public function publicIdByKey(int|string $key): ?string { $this->calls[] = 'public_id_by_key'; return $this->party($key)?->publicId(); }
    public function publicIdsByKeys(array $keys): array { $this->calls[] = 'public_ids_by_keys'; $out=[]; foreach ($keys as $key) if ($party=$this->party($key)) $out[$key]=$party->publicId(); return $out; }
    public function activeSearch(string $search, int $limit): array { $this->calls[] = 'search'; return $limit === 0 ? [] : [$this->party(1)]; }
    public function activePeopleByEmail(string $email, int $limit = 2): array { $this->calls[] = 'email'; return $email === 'a@example.test' ? [$this->party(1)] : []; }
    public function activePeopleByEmails(array $emails, int $limitPerEmail = 2): array { $this->calls[] = 'emails'; $out=[]; foreach ($emails as $email) $out[$email] = $email === 'a@example.test' ? [$this->party(1), $this->party(2)] : []; return $out; }
    public function searchActivePeople(string $search, int $limit, array $excludedKeys = []): array { $this->calls[] = 'people'; return [new PersonDirectoryEntry(1, 'public-1', 'name-1', null)]; }
    public function orderByDisplayLabel(Builder $query, string $partyKeyColumn, string $direction = 'asc'): Builder { return $query; }
    public function whereDisplayLabelMatches(Builder $query, string $partyKeyColumn, string $search): Builder { return $query; }
}

class SampleUsers implements ActorDirectory
{
    public array $calls = [];
    public array $publicIdInputs = [];
    public bool $denyKeys = false;
    public ?Exception $failure = null;
    private function actor(int|string|null $key): ?Actor { return in_array((string) $key, ['1', '2'], true) ? new SampleIdentity((int) $key) : null; }
    public function findByKey(int|string|null $key, bool $includeDeleted = false): ?Actor { $this->calls[] = 'key'; if ($this->failure !== null) throw $this->failure; if ($this->denyKeys) throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; return $this->actor($key); }
    public function findByKeys(array $keys, bool $includeDeleted = false): array { $this->calls[] = 'keys'; if ($this->denyKeys) throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; return array_values(array_filter(array_map(fn ($key) => $this->actor($key), array_unique($keys)))); }
    public function requireByKey(int|string $key, bool $includeDeleted = false): Actor { return $this->findByKey($key, $includeDeleted) ?? throw new ModelNotFoundException; }
    public function findByPublicId(string $id): ?Actor { $this->calls[] = 'public_id'; $this->publicIdInputs[] = $id; if ($this->failure !== null) throw $this->failure; return $this->actor(substr($id, 7)); }
    public function requireByPublicId(string $id): Actor { return $this->findByPublicId($id) ?? throw new ModelNotFoundException; }
    public function findByPartyKey(int|string $key): ?Actor { $this->calls[] = 'party_key'; return $this->actor($key); }
    public function keyByPublicId(string $id): int|string|null { $this->calls[] = 'key_by_public_id'; return $id === 'public-1' ? 1 : null; }
    public function publicIdByKey(int|string $key): ?string { $this->calls[] = 'public_id_by_key'; return $this->actor($key)?->publicId(); }
    public function publicIdsByKeys(array $keys): array { $this->calls[] = 'public_ids_by_keys'; $out=[]; foreach ($keys as $key) if ($actor=$this->actor($key)) $out[$key]=$actor->publicId(); return $out; }
    public function displayLabelByKey(int|string $key): ?string { $this->calls[] = 'display'; return $this->actor($key)?->displayLabel(); }
    public function activeByPublicIdForLegalEntity(string $id, int|string $legalEntityKey): ?Actor { $this->calls[] = 'active_id'; return $legalEntityKey === 10 ? $this->actor(substr($id, 7)) : null; }
    public function existsByKey(int|string $key, bool $includeDeleted = false): bool { $this->calls[] = 'exists'; return $this->actor($key) !== null; }
    public function activeForLegalEntity(int|string $legalEntityKey): array { $this->calls[] = 'active_entity'; return $legalEntityKey === 10 ? [$this->actor(1)] : []; }
}

final class StrictRequiredUsers extends SampleUsers
{
    public ?Exception $requiredFailure = null;
    public array $requiredInputs = [];
    public function requireByKey(int|string $key, bool $includeDeleted = false): Actor
    {
        $this->calls[] = 'require_key';
        $this->requiredInputs[] = [$key, $includeDeleted];
        if ($this->requiredFailure !== null) throw $this->requiredFailure;
        return (string) $key === '1' ? new SampleIdentity(1) : throw new ModelNotFoundException;
    }
    public function requireByPublicId(string $id): Actor
    {
        $this->calls[] = 'require_public_id';
        if ($this->requiredFailure !== null) throw $this->requiredFailure;
        return $id === 'public-1' ? new SampleIdentity(1) : throw new ModelNotFoundException;
    }
}

final class StrictRequiredParties extends SampleParties
{
    public ?Exception $requiredFailure = null;
    public function requireByKey(int|string $key): Party
    {
        $this->calls[] = 'require_key';
        if ($this->requiredFailure !== null) throw $this->requiredFailure;
        return (string) $key === '1' ? new SampleIdentity(1) : throw new ModelNotFoundException;
    }
    public function requireByPublicId(string $id): Party
    {
        $this->calls[] = 'require_public_id';
        if ($this->requiredFailure !== null) throw $this->requiredFailure;
        return $id === 'public-1' ? new SampleIdentity(1) : throw new ModelNotFoundException;
    }
}

final class SampleBatchUsers extends SampleUsers implements BatchActorDirectory
{
    public ?Exception $batchFailure = null;
    public array $batchInputs = [];
    public function findByPublicIds(array $publicIds): array
    {
        $this->calls[] = 'public_ids_batch';
        $this->batchInputs[] = $publicIds;
        if ($this->batchFailure !== null) {
            throw $this->batchFailure;
        }
        return $publicIds === [] ? [] : [new SampleIdentity(2), new SampleIdentity(1), new SampleIdentity(2)];
    }
}

final class SampleCanonicalParties extends SampleParties implements BatchCanonicalPartyDirectory
{
    public ?Exception $batchFailure = null;
    public array $batchInputs = [];
    public function findCanonicalByPublicIds(array $publicIds): array
    {
        $this->calls[] = 'canonical_batch';
        $this->batchInputs[] = $publicIds;
        if ($this->batchFailure !== null) {
            throw $this->batchFailure;
        }
        // Two original Party identities can lead to the same surviving Party.
        return $publicIds === [] ? [] : [new SampleIdentity(2), new SampleIdentity(2)];
    }
}

final class LegacyParties implements PartyDirectory
{
    public function __construct(public SampleParties $delegate) {}
    public function findByPublicId(string $id): ?Party { return $this->delegate->findByPublicId($id); }
    public function findCanonicalByPublicId(string $id): ?Party { return $this->delegate->findCanonicalByPublicId($id); }
    public function requireByPublicId(string $id): Party { return $this->delegate->requireByPublicId($id); }
    public function exists(string $id): bool { return $this->delegate->exists($id); }
    public function findByPublicIds(array $ids): array { return $this->delegate->findByPublicIds($ids); }
    public function findByKey(int|string $key): ?Party { return $this->delegate->findByKey($key); }
    public function findByKeys(array $keys): array { return $this->delegate->findByKeys($keys); }
    public function requireByKey(int|string $key): Party { return $this->delegate->requireByKey($key); }
    public function publicIdByKey(int|string $key): ?string { return $this->delegate->publicIdByKey($key); }
    public function publicIdsByKeys(array $keys): array { return $this->delegate->publicIdsByKeys($keys); }
    public function activeSearch(string $search, int $limit): array { return $this->delegate->activeSearch($search, $limit); }
    public function activePeopleByEmail(string $email, int $limit = 2): array { return $this->delegate->activePeopleByEmail($email, $limit); }
    public function searchActivePeople(string $search, int $limit, array $excludedKeys = []): array { return $this->delegate->searchActivePeople($search, $limit, $excludedKeys); }
    public function orderByDisplayLabel(Builder $query, string $partyKeyColumn, string $direction = 'asc'): Builder { return $query; }
    public function whereDisplayLabelMatches(Builder $query, string $partyKeyColumn, string $search): Builder { return $query; }
}

class SampleProfiles implements PersonProfileDirectory
{
    public array $calls = [];
    public function findByPartyKey(int|string $partyKey): ?PersonProfileView { $this->calls[] = 'single'; return (int) $partyKey === 1 ? new PersonProfileView('public-1', 'name-1', null, [], null, null, null, '1') : null; }
}
final class SampleBatchProfiles extends SampleProfiles implements BatchPersonProfileDirectory
{
    public array $batchInputs = [];
    public function findByPartyKeys(array $keys): array { $this->calls[] = 'batch'; $this->batchInputs[] = $keys; return in_array(1, $keys, true) ? [1 => new PersonProfileView('public-1', 'name-1', null, [], null, null, null, '1')] : []; }
}
final class SelfOnlyProfiles implements PersonProfileDirectory, BatchPersonProfileDirectory
{
    public array $calls = [];
    public function findByPartyKey(int|string $partyKey): ?PersonProfileView
    {
        return $this->findByPartyKeys([$partyKey])[$partyKey] ?? null;
    }
    public function findByPartyKeys(array $partyKeys): array
    {
        $this->calls[] = $partyKeys;
        if (count($partyKeys) !== 1 || (string) $partyKeys[0] !== '1') {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
        }
        return [1 => new PersonProfileView('public-1', 'name-1', null, [], null, null, null, '1')];
    }
}
final class SingleOnlyProfiles implements PersonProfileDirectory, BatchPersonProfileDirectory
{
    public array $calls = [];
    public bool $denySingle = false;
    public function findByPartyKey(int|string $partyKey): ?PersonProfileView
    {
        $this->calls[] = 'single';
        if ($this->denySingle) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
        }
        return (string) $partyKey === '1' ? new PersonProfileView('public-1', 'name-1', null, [], null, null, null, '1') : null;
    }
    public function findByPartyKeys(array $partyKeys): array
    {
        $this->calls[] = 'batch';
        throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
    }
}
final class SampleBusiness implements PartyBusinessDirectory, PartyRelationshipDirectory
{
    public array $calls = [];
    public bool $deny = false;
    public function activeRoles(Actor $actor, array $ids): array { $this->calls[] = ['roles', $actor, $ids]; if ($this->deny) throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException; return [$ids[0] => [['code' => 'owner']]]; }
    public function activeRelationships(Actor $actor, array $ids): array { $this->calls[] = ['relationships', $actor, $ids]; return [$ids[0] => [['code' => 'parent']]]; }
    public function search(Actor $actor, string $query, array $roles, array $types, int $page, int $perPage): array { $this->calls[] = ['search', $actor, $roles, $types]; return ['items'=>[['public_id'=>'public-1']], 'has_more'=>true]; }
    public function findVisibleByPublicId(Actor $actor, string $id): ?PartyRelationship { $this->calls[] = ['relationship', $actor, $id]; return new PartyRelationship($id, 'parent', 1, 'public-2', 'name-2', null, true); }
}
final class AuthFirstBusiness implements PartyBusinessDirectory
{
    public array $calls = [];
    public function __construct(private Actor $allowed, private ValidationException $invalid) {}
    private function authorize(Actor $actor): void
    {
        if ($actor !== $this->allowed) throw new AuthorizationException('private-denied');
    }
    public function activeRoles(Actor $actor, array $ids): array
    {
        $this->calls[] = 'roles';
        $this->authorize($actor);
        if (in_array('', $ids, true)) throw $this->invalid;
        return [];
    }
    public function activeRelationships(Actor $actor, array $ids): array
    {
        $this->calls[] = 'relationships';
        $this->authorize($actor);
        if (in_array('', $ids, true)) throw $this->invalid;
        return [];
    }
    public function search(Actor $actor, string $query, array $roles, array $types, int $page, int $perPage): array
    {
        $this->calls[] = 'search';
        $this->authorize($actor);
        if ($page < 1 || $perPage < 1 || in_array('', $roles, true) || array_diff($types, ['person','organization']) !== []) {
            throw $this->invalid;
        }
        return ['items'=>[], 'has_more'=>false];
    }
}

$party = new SampleParties;
$user = new SampleUsers;
$profile = new SampleBatchProfiles;
$business = new SampleBusiness;
$queries = identityQueries($party, $user, $profile, $business, $business);
$actor = new SampleIdentity(1);

$strictUser = new StrictRequiredUsers;
$strictParty = new StrictRequiredParties;
$strictQueries = identityQueries($strictParty, $strictUser, $profile);
$strictUser->requiredFailure = new AuthorizationException('private-user-body');
$strictParty->requiredFailure = new AuthorizationException('private-party-body');
$result = $strictQueries->queryBatch([
    $strictQueries->query('user')->where('key',1)->required(),
    $strictQueries->query('user')->where('key',1),
    $strictQueries->query('party')->where('public_id','public-1')->required(),
    $strictQueries->query('party')->where('public_id','public-1'),
])->get();
check($result['status'] === 'partial' && $strictUser->calls === ['require_key','key']
    && $strictParty->calls === ['require_public_id','public_id']
    && $result['data'][0]['error'] === ['code'=>'FORBIDDEN','httpStatus'=>403]
    && $result['data'][1]['status'] === 'success' && $result['data'][2]['error']['code'] === 'FORBIDDEN'
    && $result['data'][3]['status'] === 'success' && ! str_contains(json_encode($result), 'private-'),
    'Required and optional queries must invoke their own host authorization and not share results');
$strictUser->requiredFailure = null;
$strictParty->requiredFailure = null;
$strictUser->calls = [];
$strictParty->calls = [];
check($strictQueries->query('user')->where('key',1)->includeDeleted()->required()->get()['status'] === 'success'
    && $strictUser->calls === ['require_key'] && $strictUser->requiredInputs[1] === [1,true],
    'Required actor key lookup must preserve includeDeleted');
check($strictQueries->query('user')->where('public_id','public-1')->required()->get()['status'] === 'success'
    && end($strictUser->calls) === 'require_public_id', 'Required actor public ID uses host contract');
check($strictQueries->query('party')->where('key',1)->required()->get()['status'] === 'success'
    && $strictParty->calls === ['require_key'], 'Required party key uses host contract');
check($strictQueries->query('party')->where('public_id','public-1')->required()->get()['status'] === 'success'
    && end($strictParty->calls) === 'require_public_id', 'Required party public ID uses host contract');
$strictUser->calls = [];
check($strictQueries->query('user')->whereIn('key',[1,2])->required()->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION'
    && $strictUser->calls === [], 'No host required-many contract: do not invent its semantics');
check($strictQueries->query('user')->where('key',99)->required()->get()['error'] === ['code'=>'NOT_FOUND','httpStatus'=>404],
    'Required missing actor uses standard not-found response');
$strictUser->calls = [];
$result = $strictQueries->queryBatch([$strictQueries->query('user')->where('key',1)->required(),
    $strictQueries->query('user')->where('key',1)->required()])->get();
check($strictUser->calls === ['require_key'] && $result['status'] === 'success',
    'Identical required lookups may share only the required host operation');

$extraChecks = [
    fn () => $queries->query('user', null, 'extra'),
    fn () => $queries->queryBatch([], 'extra'),
    fn () => $queries->query('user')->where('key', '>', 1),
    fn () => $queries->query('user')->whereIn('key', [1], 'or'),
    fn () => $queries->query('user')->search('a', 'extra'),
    fn () => $queries->query('user')->limit(1, 'extra'),
    fn () => $queries->query('user')->excludeKeys([], 'extra'),
    fn () => $queries->query('user')->includeDeleted(true, 'extra'),
    fn () => $queries->query('user')->canonical(true, 'extra'),
    fn () => $queries->query('user')->required(true, 'extra'),
    fn () => $queries->query('user')->select('key', 'extra'),
    fn () => $queries->query('user')->get('extra'),
    fn () => $queries->query('party_business',$actor)->where('public_id', '>', 'x'),
    fn () => $queries->query('party_business',$actor)->whereIn('role', ['x'], 'or'),
    fn () => $queries->query('party_business',$actor)->search('x', 'extra'),
    fn () => $queries->query('party_business',$actor)->select('roles', 'extra'),
    fn () => $queries->query('party_business',$actor)->page(1, 20, 'extra'),
    fn () => $queries->query('party_business',$actor)->get('extra'),
    fn () => $queries->queryBatch([])->get('extra'),
];
foreach ($extraChecks as $index => $operation) {
    try {
        $operation();
        throw new RuntimeException('Extra argument accepted at index '.$index);
    } catch (InvalidArgumentException) {
    }
}

$batchUsers = new SampleBatchUsers;
$canonicalParties = new SampleCanonicalParties;
$latestQueries = identityQueries($canonicalParties, $batchUsers, $profile);
$userIds = ['public-1','public-2','public-1','missing'];
$result = $latestQueries->query('user')->whereIn('public_id',$userIds)->get();
check($result['status'] === 'success' && $batchUsers->calls === ['public_ids_batch'] && $batchUsers->batchInputs === [$userIds]
    && array_map(fn ($item) => $item->key(), $result['data']) === [2,1,2], 'Optional actor bulk must preserve host result order and multiplicity');
$partyIds = ['merged','merged-again','merged','missing'];
$result = $latestQueries->query('party')->whereIn('public_id',$partyIds)->canonical()->get();
check($result['status'] === 'success' && $canonicalParties->calls === ['canonical_batch'] && $canonicalParties->batchInputs === [$partyIds]
    && array_map(fn ($item) => $item->key(), $result['data']) === [2,2], 'Optional canonical bulk must preserve identical survivors returned for distinct merge histories');
$batchUsers->calls = [];
$canonicalParties->calls = [];
check($latestQueries->query('user')->whereIn('public_id',[])->get()['data'] === [] && $batchUsers->calls === ['public_ids_batch'], 'Empty actor batch uses optional contract without fallback');
check($latestQueries->query('party')->whereIn('public_id',[])->canonical()->get()['data'] === [] && $canonicalParties->calls === ['canonical_batch'], 'Empty canonical batch uses optional contract without fallback');
$largeIds = array_map(static fn (int $i): string => 'public-'.$i, range(1, 205));
$batchUsers->calls = [];
$canonicalParties->calls = [];
check($latestQueries->query('user')->whereIn('public_id',$largeIds)->get()['status'] === 'success'
    && $batchUsers->calls === ['public_ids_batch'] && end($batchUsers->batchInputs) === $largeIds, 'Actor batch delegates large input once; host owns chunking');
check($latestQueries->query('party')->whereIn('public_id',$largeIds)->canonical()->get()['status'] === 'success'
    && $canonicalParties->calls === ['canonical_batch'] && end($canonicalParties->batchInputs) === $largeIds, 'Canonical batch delegates large input once; host owns chunking');
$batchUsers->calls = [];
$canonicalParties->calls = [];
check($latestQueries->query('user')->whereIn('public_id',['public-1', 7])->get()['error']['code'] === 'INVALID_ARGUMENT'
    && $batchUsers->calls === [], 'Actor malformed IDs rejected before dispatch');
check($latestQueries->query('party')->whereIn('public_id',['merged', 7])->canonical()->get()['error']['code'] === 'INVALID_ARGUMENT'
    && $canonicalParties->calls === [], 'Canonical malformed IDs rejected before dispatch');
check($latestQueries->query('user')->whereIn('public_id',$userIds)->includeDeleted()->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION'
    && $batchUsers->calls === [], 'Actor public ID batch must not discard deletion scope');
check($latestQueries->query('party')->whereIn('public_id',$partyIds)->where('active',true)->canonical()->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION'
    && $canonicalParties->calls === [], 'Canonical batch must not discard extra conditions');
foreach ([403 => 'FORBIDDEN', 422 => 'INVALID_QUERY'] as $status => $code) {
    $batchUsers->calls = [];
    $batchUsers->batchFailure = new \Symfony\Component\HttpKernel\Exception\HttpException($status);
    $result = $latestQueries->query('user')->whereIn('public_id',$userIds)->get();
    check($batchUsers->calls === ['public_ids_batch'] && $result['error']['code'] === $code, 'Actor bulk failure must not retry single lookups');
    $canonicalParties->calls = [];
    $canonicalParties->batchFailure = new \Symfony\Component\HttpKernel\Exception\HttpException($status);
    $result = $latestQueries->query('party')->whereIn('public_id',$partyIds)->canonical()->get();
    check($canonicalParties->calls === ['canonical_batch'] && $result['error']['code'] === $code, 'Canonical bulk failure must not retry single lookups');
}
$batchUsers->batchFailure = null;
$canonicalParties->batchFailure = null;
$user->calls = [];
$user->publicIdInputs = [];
$result = $queries->query('user')->whereIn('public_id',$userIds)->get();
check($user->publicIdInputs === ['public-1','public-2','missing']
    && array_map(fn ($item) => $item->key(), $result['data']) === [1,2], 'Legacy actor fallback deduplicates inputs, keeps input order and omits missing');
$party->calls = [];
$party->canonicalInputs = [];
$result = $queries->query('party')->whereIn('public_id',$partyIds)->canonical()->get();
check($party->canonicalInputs === ['merged','merged-again','missing']
    && array_map(fn ($item) => $item->key(), $result['data']) === [2,2], 'Legacy canonical fallback retains distinct merge histories');
foreach ([403 => 'FORBIDDEN', 422 => 'INVALID_QUERY'] as $status => $code) {
    $user->calls = [];
    $user->publicIdInputs = [];
    $user->failure = new \Symfony\Component\HttpKernel\Exception\HttpException($status);
    $result = $queries->query('user')->whereIn('public_id',$userIds)->get();
    check($user->calls === ['public_id'] && $user->publicIdInputs === ['public-1'] && $result['error']['code'] === $code, 'Legacy actor error must stop fallback');
    $user->failure = null;
    $party->calls = [];
    $party->canonicalInputs = [];
    $party->canonicalFailure = new \Symfony\Component\HttpKernel\Exception\HttpException($status);
    $result = $queries->query('party')->whereIn('public_id',$partyIds)->canonical()->get();
    check($party->calls === ['canonical'] && $party->canonicalInputs === ['merged'] && $result['error']['code'] === $code, 'Legacy canonical error must stop fallback');
    $party->canonicalFailure = null;
}
$user->calls = [];
$party->calls = [];

$result = $queries->query('party')->whereIn('key', [1,2])->get();
check($result['status'] === 'success' && count($result['data']) === 2 && $party->calls === ['keys'], 'Party bulk contract');
$party->calls = [];
$result = $queries->query('party')->where('public_id','merged')->canonical()->get();
check($result['data'][0]->key() === 2 && $party->calls === ['canonical'], 'Canonical lookup');
$party->calls = [];
$result = $queries->query('party')->where('key',99)->required()->get();
check($result['error']['code'] === 'NOT_FOUND', 'Required missing result');
$party->calls = [];
$result = $queries->query('party')->where('public_id','public-1')->select('exists')->get();
check($result['data'] === [true] && $party->calls === ['exists'], 'Party exists must use host projection');
$party->calls = [];
$result = $queries->query('party')->whereIn('key',[1,2])->select('public_id')->get();
check($result['data'] === [['key'=>1,'value'=>'public-1'],['key'=>2,'value'=>'public-2']] && $party->calls === ['public_ids_by_keys'], 'Party conversion bulk');
$party->calls = [];
$result = $queries->query('party')->whereIn('email',['a@example.test','missing@example.test'])->get();
check($party->calls === ['emails'] && count($result['data'][0]['value']) === 2 && $result['data'][1]['value'] === [], 'Email grouping');
$party->calls = [];
$result = $queries->query('party')->whereIn('email',['missing@example.test'])->select('exists')->get();
check($result['status'] === 'error' && $result['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION' && $party->calls === [], 'Email group existence must not claim an empty group is a match');
$result = $queries->query('party')->whereIn('email',['missing@example.test'])->required()->get();
check($result['status'] === 'error' && $result['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION' && $party->calls === [], 'Email group required semantics must be explicit');
$result = $queries->query('party')->whereIn('email',['a@example.test','missing@example.test'])->required()->get();
check($result['status'] === 'error' && $result['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION' && $party->calls === [], 'Mixed email group required must not silently select any/all semantics');
$legacyPartyDelegate = new SampleParties;
$legacyPartyQueries = identityQueries(new LegacyParties($legacyPartyDelegate), $user, $profile);
$result = $legacyPartyQueries->query('party')->whereIn('email',[' A@example.test ','a@example.test','missing@example.test'])->get();
check($legacyPartyDelegate->calls === ['email','email'] && $result['data'][0]['key'] === 'a@example.test'
    && count($result['data'][0]['value']) === 1 && $result['data'][1]['value'] === [], 'Legacy email fallback normalizes and deduplicates');
$result = $queries->query('party')->where('active',true)->where('type','person')->search('name')->get();
check($result['data'][0] instanceof PersonDirectoryEntry, 'Search must retain person directory entry');
$result = $queries->query('party')->where('active',true)->where('type','person')->search('name')->select('public_id')->get();
check($result['data'] === [['key'=>1,'value'=>'public-1']], 'Person directory public properties must support projection');
check($queries->query('party')->where('active',true)->where('type','person')->search('name')->select('display_label')->get()['data'] === [['key'=>1,'value'=>'name-1']], 'Person directory display projection');
check($queries->query('party')->where('active',true)->where('type','person')->search('name')->select('key')->get()['data'] === [['key'=>1,'value'=>1]], 'Person directory key projection');
$party->calls = [];
$result = $queries->query('party')->where('key',1)->where('active',true)->get();
check($result['status'] === 'error' && $party->calls === [], 'Unsupported condition must not be ignored');
$result = $queries->query('party')->where('active',null)->where('email','a@example.test')->get();
check($result['status'] === 'error', 'Null condition must not be ignored');

$result = $queries->query('user')->where('public_id','public-1')->select('key')->get();
check($result['data'] === [['key'=>1,'value'=>1]] && $user->calls === ['key_by_public_id'], 'Actor key projection');
$user->calls = [];
$result = $queries->query('user')->where('key',1)->select('exists')->get();
check($result['data'] === [true] && $user->calls === ['exists'], 'Actor exists projection');
$user->calls = [];
$result = $queries->query('user')->whereIn('key',[1,2])->select('public_id')->get();
check($result['data'] === [['key'=>1,'value'=>'public-1'],['key'=>2,'value'=>'public-2']] && $user->calls === ['public_ids_by_keys'], 'Actor public ID bulk projection');
$user->calls = [];
$result = $queries->query('user')->where('key',1)->select('display_label')->get();
check($result['data'] === [['key'=>1,'value'=>'name-1']] && $user->calls === ['display'], 'Actor display projection');
$user->calls = [];
$result = $queries->query('user')->where('party_key',1)->get();
check($result['data'][0]->key() === 1 && $user->calls === ['party_key'], 'Actor Party lookup');
$user->calls = [];
$result = $queries->query('user')->where('key',null)->get();
check($result['data'] === [] && $user->calls === ['key'], 'Nullable Actor key');
$user->calls = [];
$result = $queries->query('user')->where('legal_entity_key',10)->where('active',true)->get();
check(count($result['data']) === 1 && $user->calls === ['active_entity'], 'Actor entity scope');
$result = $queries->query('user')->where('legal_entity_key',11)->where('active',true)->get();
check($result['data'] === [], 'Other entity must not leak');
$result = $queries->query('user')->where('public_id','public-1')->where('legal_entity_key',10)->where('active',true)->get();
check($result['data'][0]->key() === 1 && end($user->calls) === 'active_id', 'Actor active public ID scope');

$result = $queries->query('profile')->whereIn('party_key',[1,2])->get();
check($profile->calls === ['batch'] && $result['data'][0]['key'] === 1 && $result['data'][0]['value'] instanceof PersonProfileView, 'Profile keyed bulk');
$profile->calls = [];
$rawKeys = ['', null, 1, 1];
$result = $queries->query('profile')->whereIn('party_key',$rawKeys)->get();
check($result['status'] === 'success' && $profile->calls === ['batch'] && end($profile->batchInputs) === $rawKeys,
    'Batch profile host retains its own input normalization');
$legacy = new SampleProfiles;
$legacyQueries = identityQueries($party, $user, $legacy);
$result = $legacyQueries->query('profile')->whereIn('party_key',[1,2,1])->get();
check($legacy->calls === ['single','single'] && count($result['data']) === 1, 'Legacy profile fallback with deduplication');
$legacy->calls = [];
$result = $legacyQueries->query('profile')->whereIn('party_key',['', null, 1, 1])->get();
check($result['status'] === 'success' && $legacy->calls === ['single'] && count($result['data']) === 1,
    'Legacy profile fallback filters blank keys and deduplicates');
$selfOnly = new SelfOnlyProfiles;
$expectedSelf = $selfOnly->findByPartyKey(1);
try {
    $selfOnly->findByPartyKey(2);
    throw new RuntimeException('Old self-only authorization was not applied');
} catch (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
}
$oldCalls = $selfOnly->calls;
$selfOnly->calls = [];
$selfQueries = identityQueries($party, $user, $selfOnly);
$result = $selfQueries->queryBatch([$selfQueries->query('profile')->where('party_key',1), $selfQueries->query('profile')->where('party_key',2)])->get();
check($selfOnly->calls === $oldCalls && $result['status'] === 'partial'
    && $result['data'][0]['data'][0]['value'] == $expectedSelf && $result['data'][1]['error']['code'] === 'FORBIDDEN',
    'Self-only mixed identity calls retain individual authorization and results');
$selfOnly->calls = [];
$result = $selfQueries->queryBatch([$selfQueries->query('profile')->where('party_key',1), $selfQueries->query('profile')->where('party_key',1)])->get();
check($selfOnly->calls === [[1]] && $result['data'][0]['status'] === 'success' && $result['data'][1]['status'] === 'success',
    'Same self-only identity read may be shared');
$singleOnly = new SingleOnlyProfiles;
$singleQueries = identityQueries($party, $user, $singleOnly);
$result = $singleQueries->queryBatch([$singleQueries->query('profile')->where('party_key',1), $singleQueries->query('profile')->where('party_key',1)])->get();
check($singleOnly->calls === ['single'] && $result['data'][0]['status'] === 'success' && $result['data'][1]['status'] === 'success',
    'Repeated single lookups must not use a separately authorized bulk operation');
$singleOnly->calls = [];
$result = $singleQueries->queryBatch([$singleQueries->query('profile')->where('party_key',2)->required(), $singleQueries->query('profile')->where('party_key',2)])->get();
check($singleOnly->calls === ['single'] && $result['status'] === 'partial' && $result['data'][0]['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION' && $result['data'][1]['data'] === [],
    'Profile has no required lookup; ordinary lookup must remain independent');
$singleOnly->calls = [];
$singleOnly->denySingle = true;
$result = $singleQueries->queryBatch([$singleQueries->query('profile')->where('party_key',1), $singleQueries->query('profile')->where('party_key',1)])->get();
check($singleOnly->calls === ['single'] && $result['data'][0]['error']['code'] === 'FORBIDDEN' && $result['data'][1]['error']['code'] === 'FORBIDDEN',
    'Shared denied single lookup must not switch contracts or retry');
$singleOnly->denySingle = false;

$result = $queries->query('party_business',$actor)->search('x')->whereIn('role',['owner'])->whereIn('type',['person'])->page(2,10)->get();
check($result['page']['hasMore'] === true && $business->calls[0][1] === $actor && $business->calls[0][2] === ['owner'], 'Business actor and paging');
$result = $queries->query('party_business',$actor)->whereIn('public_id',['public-1'])->select('roles')->get();
check($result['data'][0]['key'] === 'public-1' && $business->calls[1][1] === $actor, 'Roles actor and mapping');
$result = $queries->query('party_business',$actor)->whereIn('public_id',['public-1'])->select('relationships')->get();
check($result['data'][0]['key'] === 'public-1' && $business->calls[2][1] === $actor, 'Relationships actor and mapping');
$result = $queries->query('party_business',$actor)->where('public_id','relation-1')->select('relationship')->get();
check($result['data'][0] instanceof PartyRelationship && $business->calls[3][1] === $actor, 'Relationship actor');
$result = identityQueries($party,$user,$profile,$business)->query('party_business',$actor)->where('public_id','relation-1')->select('relationship')->get();
check($result['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION', 'Unavailable relationship capability');
check($queries->query('party_business')->search('x')->get()['error']['code'] === 'INVALID_ARGUMENT', 'Missing actor must return a standard error');
check($queries->query('unknown')->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION', 'Unknown target must return a standard error');
check($queries->query('party', $actor)->where('key',1)->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION', 'Explicit actor must not be silently ignored');
$business->deny = true;
$result = $queries->query('party_business',$actor)->whereIn('public_id',['public-1'])->select('roles')->get();
check($result['error']['code'] === 'FORBIDDEN' && $result['error']['httpStatus'] === 403 && count($business->calls) === 5, 'Authorization denial is preserved without retry');
$business->deny = false;

$user->calls = [];
$first = $queries->query('user')->where('key',1);
$result = $queries->queryBatch([$first, $queries->query('user')->where('key',99)->required(), $queries->query('user')->where('key',2)])->get();
check($user->calls === ['key','key','key'] && $result['status'] === 'partial' && $result['data'][0]['data'][0]->key() === 1 && $result['data'][1]['error']['code'] === 'NOT_FOUND' && $result['data'][2]['data'][0]->key() === 2, 'Different keys preserve independent authorization and partial results');
$user->calls = [];
$result = $queries->queryBatch([$queries->query('user')->where('key',2), $queries->query('user')->where('key',2), $queries->query('user')->where('key',1), $queries->query('user')->where('key',99)])->get();
check($user->calls === ['key','key','key'] && $result['data'][0]['data'][0]->key() === 2 && $result['data'][1]['data'][0]->key() === 2 && $result['data'][2]['data'][0]->key() === 1 && $result['data'][3]['data'] === [], 'Duplicate requests share one single read while different keys retain order and missing results');
$user->calls = [];
$user->denyKeys = true;
$result = $queries->queryBatch([$queries->query('user')->where('key',1), $queries->query('user')->where('key',2), $queries->query('party')->where('key',1)])->get();
check($user->calls === ['key','key'] && $result['status'] === 'partial' && $result['data'][0]['error']['code'] === 'FORBIDDEN' && $result['data'][1]['error']['code'] === 'FORBIDDEN' && $result['data'][2]['status'] === 'success', 'An authorization error must not be retried and must not erase other results');
$user->denyKeys = false;
$party->calls = [];
$result = $queries->queryBatch([$queries->query('party')->where('public_id','PUBLIC-1'), $queries->query('party')->where('public_id','public-1')])->get();
check($party->calls === ['public_id','public_id'] && $result['data'][0]['data'] === [] && $result['data'][1]['data'][0]->key() === 1, 'Case-sensitive public ID must not use case-normalizing bulk lookup');
$party->calls = [];
$result = $queries->queryBatch([$queries->query('party')->where('public_id','public-2'), $queries->query('party')->where('public_id','public-2'), $queries->query('party')->where('public_id','public-1'), $queries->query('party')->where('public_id','public-99')])->get();
check($party->calls === ['public_id','public_id','public_id'] && $result['data'][0]['data'][0]->key() === 2 && $result['data'][1]['data'][0]->key() === 2 && $result['data'][2]['data'][0]->key() === 1 && $result['data'][3]['data'] === [], 'Public ID single-read duplicate sharing, order and missing');
$user->calls = [];
$result = $queries->queryBatch([$queries->query('user')->where('key',1)->includeDeleted(), $queries->query('user')->where('key',2)])->get();
check($user->calls === ['key','key'] && $result['status'] === 'success', 'Different include-deleted scopes must not be combined');
check($first->where('active',true)->get()['status'] === 'error' && $first->get()['status'] === 'success', 'Immutable builder');

$single = $queries->query('party')->where('key',1)->get()['data'][0] ?? null;
check($single == $party->findByKey(1), 'Party single parity');
check($queries->query('party')->whereIn('key',[2,1])->get()['data'] == $party->findByKeys([2,1]), 'Party multi-key parity');
check($queries->query('party')->where('public_id','public-1')->get()['data'][0] == $party->findByPublicId('public-1'), 'Party public ID parity');
check($queries->query('party')->whereIn('public_id',['public-2','public-1'])->get()['data'] == array_values($party->findByPublicIds(['public-2','public-1'])), 'Party public IDs parity');
check($queries->query('party')->where('active',true)->search('name')->limit(5)->get()['data'] == $party->activeSearch('name',5), 'Party search parity');
check($queries->query('party')->where('email','a@example.test')->limit(2)->get()['data'] == $party->activePeopleByEmail('a@example.test',2), 'Party email parity');
check($queries->query('party')->where('active',true)->where('type','person')->search('name')->limit(5)->excludeKeys([3])->get()['data'] == $party->searchActivePeople('name',5,[3]), 'Person search parity');
check($queries->query('user')->where('key',1)->includeDeleted()->get()['data'][0] == $user->findByKey(1,true), 'Actor single parity');
check($queries->query('user')->whereIn('key',[2,1])->get()['data'] == $user->findByKeys([2,1]), 'Actor multi-key parity');
check($queries->query('user')->where('public_id','public-1')->get()['data'][0] == $user->findByPublicId('public-1'), 'Actor public ID parity');
check($queries->query('profile')->where('party_key',1)->get()['data'][0]['value'] == $profile->findByPartyKey(1), 'Profile single parity');

$container = new Container;
$container->instance(ContainerContract::class, $container);
$container->instance(PartyDirectory::class, $party);
$partial = $container->make(IdentityQueries::class);
check($partial->query('party')->where('key',1)->get()['status'] === 'success', 'Partial host autowiring');
check($partial->query('user')->where('key',1)->get()['error']['code'] === 'UNSUPPORTED_IMPLEMENTATION', 'Missing host capability');

$isolated = new Container;
$isolated->instance(ContainerContract::class, $isolated);
$isolated->instance(ActorDirectory::class, $user);
$isolated->instance(PartyRelationshipDirectory::class, $business);
$isolated->bind(PartyDirectory::class, static function () { throw new RuntimeException('private-party-binding-error'); });
$isolated->bind(PartyBusinessDirectory::class, static function () { throw new RuntimeException('private-business-binding-error'); });
$isolatedQueries = $isolated->make(IdentityQueries::class);
check($isolatedQueries->query('user')->where('key',1)->get()['status'] === 'success', 'Unrelated broken Party binding must not block Actor query');
check($isolatedQueries->query('party_business',$actor)->where('public_id','relation-1')->select('relationship')->get()['status'] === 'success',
    'Unrelated broken business binding must not block relationship query');
$failed = $isolatedQueries->query('party')->where('key',1)->get();
check($failed['error']['code'] === 'QUERY_FAILED' && ! str_contains(json_encode($failed), 'private-party-binding-error'),
    'Selected binding failure returns sanitized standard error');
$failed = $isolatedQueries->query('party_business',$actor)->search('test')->get();
check($failed['error']['code'] === 'QUERY_FAILED' && ! str_contains(json_encode($failed), 'private-business-binding-error'),
    'Selected business binding failure returns sanitized standard error');
$isolated->bind(PartyDirectory::class, static fn () => new stdClass);
check($isolatedQueries->query('party')->where('key',1)->get()['error']['code'] === 'QUERY_FAILED',
    'Incorrect host binding is a sanitized error rather than a type crash');
$transient = new Container;
$transient->instance(ContainerContract::class, $transient);
$firstDirectory = new SampleUsers;
$secondDirectory = new SampleUsers;
$secondDirectory->failure = new AuthorizationException('private-scoped-denial');
$resolutions = 0;
$transient->bind(ActorDirectory::class, static function () use (&$resolutions, $firstDirectory, $secondDirectory) {
    return ++$resolutions === 1 ? $firstDirectory : $secondDirectory;
});
$transientQueries = $transient->make(IdentityQueries::class);
$result = $transientQueries->queryBatch([$transientQueries->query('user')->where('key',1),
    $transientQueries->query('user')->where('key',1)])->get();
check($resolutions === 2 && $result['status'] === 'partial' && $result['data'][0]['status'] === 'success'
    && $result['data'][1]['error']['code'] === 'FORBIDDEN',
    'Transient directory bindings must preserve independent authority and not share results');

$translator = new \Illuminate\Translation\Translator(new \Illuminate\Translation\ArrayLoader, 'en');
$validator = new \Illuminate\Validation\Validator($translator, [], ['private'=>'required']);
$validator->fails();
$invalid = new ValidationException($validator);
$authFirst = new AuthFirstBusiness($actor, $invalid);
$authQueries = identityQueries($party, $user, $profile, $authFirst);
$deniedActor = new SampleIdentity(2);
$denied = $authQueries->query('party_business',$deniedActor)->search('x')->whereIn('type',['invalid'])->page(0,20)->get();
check($denied['error'] === ['code'=>'FORBIDDEN','httpStatus'=>403] && $authFirst->calls === ['search'],
    'Business provider must authorize before validating malformed values');
$authFirst->calls = [];
$invalidPage = $authQueries->query('party_business',$actor)->search('x')->page(0,20)->get();
check($invalidPage['error'] === ['code'=>'INVALID_QUERY','httpStatus'=>422] && $authFirst->calls === ['search'],
    'Business invalid page preserves host validation error');
$authFirst->calls = [];
$invalidId = $authQueries->query('party_business',$actor)->whereIn('public_id',[''])->select('roles')->get();
check($invalidId['error'] === ['code'=>'INVALID_QUERY','httpStatus'=>422] && $authFirst->calls === ['roles'],
    'Business blank public ID preserves host validation error');
$authFirst->calls = [];
$deniedId = $authQueries->query('party_business',$deniedActor)->whereIn('public_id',[''])->select('roles')->get();
check($deniedId['error'] === ['code'=>'FORBIDDEN','httpStatus'=>403] && $authFirst->calls === ['roles'],
    'Business invalid ID must not bypass host authorization');
$frameworkErrors = [
    [$invalid, ['code'=>'INVALID_QUERY','httpStatus'=>422]],
    [new AuthorizationException('private auth message'), ['code'=>'FORBIDDEN','httpStatus'=>403]],
    [(new AuthorizationException('private hidden message'))->asNotFound(), ['code'=>'NOT_FOUND','httpStatus'=>404]],
    [(new AuthorizationException('private throttled'))->withStatus(429), ['code'=>'RATE_LIMITED','httpStatus'=>429]],
    [new AuthenticationException('private login message'), ['code'=>'UNAUTHENTICATED','httpStatus'=>401]],
    [(new ModelNotFoundException)->setModel('private-model', [123]), ['code'=>'NOT_FOUND','httpStatus'=>404]],
];
foreach ($frameworkErrors as [$exception, $expected]) {
    $strictUser->requiredFailure = $exception;
    $result = $strictQueries->query('user')->where('key',1)->required()->get();
    check($result['error'] === $expected && ! str_contains(json_encode($result), 'private'),
        'Framework exception should preserve status without leaking details');
    check(IdentityQuery::failure($exception)['error'] === $expected, 'Framework error mapping parity');
}
$strictUser->requiredFailure = null;

foreach ([403 => ['FORBIDDEN',403], 401 => ['UPSTREAM_AUTHENTICATION_FAILED',502], 504 => ['TIMEOUT',504], 413 => ['TOO_LARGE',413]] as $status => $expected) {
    $exception = new class($status) extends RuntimeException {
        public readonly string $failureCode;
        public function __construct(public readonly int $upstreamStatus)
        {
            $this->failureCode = 'RUNTIME_RESOURCE_READ_REJECTED';
            parent::__construct('confidential-upstream-body');
        }
    };
    $result = IdentityQuery::failure($exception);
    check($result['error']['code'] === $expected[0] && $result['error']['httpStatus'] === $expected[1]
        && ! str_contains(json_encode($result), 'confidential-upstream-body'), 'Runtime upstream status must be preserved without leaking messages');
    $user->failure = $exception;
    $user->calls = [];
    $batch = $queries->queryBatch([$queries->query('user')->where('key',1), $queries->query('user')->where('key',1)])->get();
    check($user->calls === ['key'] && $batch['data'][0]['error'] === $result['error']
        && $batch['data'][1]['error'] === $result['error'] && ! str_contains(json_encode($batch), 'confidential-upstream-body'),
        'Runtime upstream errors must be preserved through batch without retry or body exposure');
}
$user->failure = null;
$unrecognized = new class extends RuntimeException {
    public readonly string $failureCode;
    public readonly int $upstreamStatus;
    public function __construct() {
        $this->failureCode = 'confidential-upstream-body';
        $this->upstreamStatus = 600;
        parent::__construct('confidential-upstream-body');
    }
};
$result = IdentityQuery::failure($unrecognized);
check($result['error'] === ['code'=>'QUERY_FAILED','httpStatus'=>null] && ! str_contains(json_encode($result), 'confidential-upstream-body'), 'Unknown runtime failures must remain sanitized');
$runtimeCode = new class extends RuntimeException {
    public readonly string $failureCode;
    public function __construct() { $this->failureCode = 'RUNTIME_REFERENCE_DEADLINE_EXCEEDED'; }
};
check(IdentityQuery::failure($runtimeCode)['error'] === ['code'=>'RUNTIME_REFERENCE_DEADLINE_EXCEEDED','httpStatus'=>504], 'Known runtime cause must retain its standard status');
check(IdentityQuery::failure(new RuntimeException('confidential-upstream-body'))['error'] === ['code'=>'QUERY_FAILED','httpStatus'=>null], 'Ordinary failure must stay sanitized');

echo "IdentityQueriesTest passed\n";
