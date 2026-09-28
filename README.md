# Nexia App SDK for Laravel

`amuzcorp/nexia-app-sdk-laravel` provides the `Nexia\*` PHP contracts,
Laravel adapters, and focused conformance helpers used by Nexia App Packages.

Install this package through the Nexia Composer repository configured by the
host. Its public namespace and wire values are preserved from the integrated
SDK package during the package split.

For package development, run `composer test` from this directory. The PHP
package does not require the React package or Node tooling.

## Reference conditions for governed writes

Use `PartyWritePreconditions` when an App save depends on selected Party properties:

```php
use Nexia\Identity\Contracts\Actor;
use Nexia\Identity\Contracts\PartyWritePreconditions;

app(PartyWritePreconditions::class)->register(
    app(Actor::class),
    $partyPublicId,
    ['person' => true, 'archived' => false],
);
// Save App-owned data after registration using your normal App transaction.
```

This requires the matching Core/runtime implementation, a host-governed mutating
request, the current actor, and `directory.party.read`. Agent custom tools must
declare that permission. Ordinary delegated resource actions do not yet support
this callback. Unsupported execution contexts reject the call.

Choose only conditions your operation needs: `person`, `organization`, and
`archived` are strict booleans. At least one is required. Use canonical lowercase
Party public IDs, with at most 100 distinct targets per request. Identical
registration is repeatable; changing a registered target's conditions is rejected.
Registration itself changes no Party or App business data.

Core checks these conditions during registration and again under its own row
locks before finalizing the App's success. If a condition changed or a Party
vanished, the operation needs review even if App data already committed. Preserve
the pending result and inspect saved data; do not blindly create another request.
Closing review retains saved data and does not report success or undo the write.

This method does not hold Core locks for the App transaction, promise a shared
rollback, or replace `requireByPublicIdForUpdate`. Organization-directory lock
methods also remain unsupported; use the explicit organization conditions below.
The draft contract needs coordinated SDK/Core/runtime adoption; package publication
and the retained local user runtime are separate from source implementation.

## Organization conditions

Use the current actor with `OrganizationWritePreconditions::register()` before App
persistence. Legal-entity activity and dated unit membership/effectiveness are
separate predicates; choose only those needed by the operation:

```php
use Nexia\Organization\Contracts\OrganizationWritePreconditions;

$conditions = app(OrganizationWritePreconditions::class);
$conditions->register(app(Actor::class), [
    'type' => 'legal_entity',
    'id' => $legalEntityPublicId,
    'expected' => ['active' => true],
]);
$conditions->register(app(Actor::class), [
    'type' => 'operating_unit',
    'id' => $operatingUnitPublicId,
    'legal_entity_id' => $legalEntityPublicId,
    'as_of' => $businessDate, // YYYY-MM-DD; effective-until is exclusive.
    'expected' => ['affiliated' => true, 'effective' => true],
]);
```

These IDs are public UUIDs. `active` uses the LegalEntity contract's active
organization meaning. `affiliated` checks the unit's legal-entity relationship on
the supplied date; `effective` checks the unit's own status and effective period.
A missing identity fails regardless of the selected boolean. Registration requires
visibility through this App's declared organization permissions and current grants.
A delegated custom tool must declare the relevant permission and retain its exact
selected organization scope, even if the actor can access other organizations.
Standard delegated create/update/delete actions use only their selected resource
permission and organization scope for these conditions. They gain no directory
read or profile-write permission.

Party and organization conditions share one 100-target request limit. Identical
registrations are repeatable; predicates for an already registered target/date
cannot be replaced. Completion checks both kinds under Core locks; a later conflict
requires review and may retain App data. Matching SDK, Core, runtime image and
operator callback routes must be adopted together. This is not a replacement for
shared-transaction lock methods or support for ordinary delegated resource actions.

## Developer support / 개발자 지원

Report SDK, setup, CLI, Docker, AI-tool and sandbox problems in the [central developer support tracker](https://github.com/nexia-cloud-os/developer-support/issues). Search existing issues first and include package versions and minimal reproduction steps. Never attach credentials, private Core source or customer data.

SDK·개발환경·CLI·Docker·AI 도구·샌드박스 문제는 [통합 이슈 창구](https://github.com/nexia-cloud-os/developer-support/issues)에 신고하세요. 기존 이슈를 먼저 검색하고 패키지 버전과 최소 재현 단계를 포함하세요. 인증 정보, 비공개 Core 소스와 고객 데이터는 첨부하지 마세요.
