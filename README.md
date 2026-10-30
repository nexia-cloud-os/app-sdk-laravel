# Nexia App SDK for Laravel

`nexia-cloud-os/sdk-laravel` provides the `Nexia\*` PHP contracts,
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

This method does not hold Core locks for the App transaction or promise a shared
rollback. Use the explicit party and organization conditions; local-only directory
lock methods are not part of the shared SDK.
This contract requires coordinated SDK/Core/runtime adoption; package publication
does not prove that the selected App runtime has adopted the contract.

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

## Reports and actions / 리포트와 액션

A Resource Module can implement `ReportContribution::reports()` and return
`ReportDefinition` values containing an existing `CompositionSpec` and presentation.
Core runs the same authorized Composition query for standalone reports and dashboard
placements. App definitions are read-only; editing saves a new user-owned report.
Ordinary Resource lists remain Resource pages. Widgets place reports, actions or
content and do not create an additional business permission.

Declare Resource operations in `ResourceDescriptor::actions` using
`Nexia\Actions\ActionDefinition`. App-wide operations belong to an
`ActionContribution` and use `ActionTargets::None`. Declare `None`, `One` or `Many`
targets and explicit `ActionPlacement` values. Human placements require a label
key; a single-target route contains `{id}` (or its declared `targetParameter`).
Keep the business handler and authorization in the owning App API. Ordinary CRUD
continues to use `ResourceMutationDescriptor`.

The public `NxActions` host component receives a resource key, placement, optional
current targets and legal-entity scope. Core binds the current selection or offers
an authorized target picker. `onSuccess` refreshes the owning view. Palette commands
are not automatically offered as dashboard widgets. Native catalogs transport inert
report/action definitions; signed requests retain existing permission and receipt
recovery checks. A matching Core and sandbox runtime is required.

리소스 모듈은 `ReportContribution::reports()`에서 기존 `CompositionSpec`과 표현
정의를 담은 `ReportDefinition`을 제공합니다. 앱 원본은 읽기 전용이며 편집하면
사용자 소유 사본으로 저장합니다. 단독 실행과 대시보드 배치는 동일한 조회·권한
계약을 사용합니다. 일반 리소스 목록은 그대로 유지하며 위젯에 별도 업무 권한을
추가하지 않습니다.

리소스 액션은 `ResourceDescriptor::actions`, 앱 전체 액션은 `ActionContribution`에
선언합니다. `ActionDefinition`의 대상 수와 허용 위치를 명시하고 기존 업무 API와
권한 검사를 재사용합니다. `NxActions`에 현재 대상과 법인 문맥을 넘기면 Core가
대상을 연결하거나 선택창을 제공합니다. 팔레트 명령은 대시보드에 자동 노출하지
않습니다. 샌드박스는 선언 데이터만 전달하며 실행·실패 복구는 기존 서명 요청
경로를 사용합니다.

## Developer support / 개발자 지원

Report SDK, setup, CLI, Docker, AI-tool and sandbox problems in the [central developer support tracker](https://github.com/nexia-cloud-os/developer-support/issues). Search existing issues first and include package versions and minimal reproduction steps. Never attach credentials, private Core source or customer data.

SDK·개발환경·CLI·Docker·AI 도구·샌드박스 문제는 [통합 이슈 창구](https://github.com/nexia-cloud-os/developer-support/issues)에 신고하세요. 기존 이슈를 먼저 검색하고 패키지 버전과 최소 재현 단계를 포함하세요. 인증 정보, 비공개 Core 소스와 고객 데이터는 첨부하지 마세요.

## Isolated App execution limits

Isolated App reports feed complete, owner-authorized public Resource projections into the existing Composition SQL planner. Grouped and conditional measures, sorting, comparisons and declared reference relationships reuse that engine. App models and DB connections stay in the runtime. Each Resource read uses a read-only repeatable-read transaction; separate resources are not one distributed snapshot. The source budget is 10,000 records and 1 MiB per Resource plus the shared execution deadline. Missing fields, duplicate identities, incomplete pages, revoked authority and exceeded budgets fail closed. Large analyses should use owner-published reporting projections. Existing result limits remain 100 rows or 500 aggregate buckets. Native reads currently use one selected Legal Entity/Operating Unit context.

## 격리 앱의 실행 범위

격리 앱 리포트는 소유 앱이 권한 검사한 완전한 공개 Resource 데이터를 기존 Composition SQL 계획기에 연결합니다. 그룹·조건부 집계, 정렬, 기간 비교와 명시적 참조 관계는 기존 엔진을 재사용합니다. 앱 모델과 DB 연결은 런타임에 유지합니다. 각 Resource 조회는 읽기 전용 repeatable-read 트랜잭션이며 서로 다른 리소스가 하나의 분산 스냅샷이라는 뜻은 아닙니다. Resource당 10,000건·1 MiB와 공통 실행 시간 제한을 적용합니다. 필드 누락·중복 식별자·불완전한 페이지·권한 회수·한도 초과는 실패로 처리합니다. 대규모 분석은 소유 앱이 공개한 리포팅 projection을 사용합니다. 결과 제한은 기존대로 행 100개·집계 구간 500개이며 Native 조회는 현재 선택한 법인·운영 단위 한 개의 문맥을 사용합니다.

## Shared host compatibility

Core-owned models retain the `HasMedia` and owner-authorization interfaces. Core
registers local media storage by calling `NexiaModel::enableLocalMedia()` in its
provider registration before any model boots. App runtimes must leave it
disabled: local media relations fail closed and model deletion does not query a
local media table. Use existing attachment host contracts in isolated Apps.
Apps require the canonical `nexia-cloud-os/sdk-laravel` package. Previous Composer
package names are not aliases; dependency locks must use the canonical identity.

### Scoped Backend settings

Declare `SettingDefinition` entries through `SettingsContribution` and require
`settings.read` in the Runtime requirements. The selected isolated Runtime implements
`Nexia\Settings\Contracts\AppSettings`; the host determines the App, Tenant and
environment. Core reads declarations from the accepted runtime catalog and
preserves the existing settings APIs. Reads require an authenticated actor and an active, host-selected App execution scope; they never infer another App from a caller-supplied key.

```php
$value = app(\Nexia\Settings\Contracts\AppSettings::class)
    ->get('tenant', 'my-app.access-key');
$result = $value->use(function (#[\SensitiveParameter] $key) {
    // Pass the value only to the intended integration. Return a safe business result.
});
```

Use `app` explicitly for a shared App setting. Identically named keys in the two
scopes never fall back to each other. A missing required value blocks the lookup;
there is no implicit activation-wide requirement. Values are read afresh on each
call. Replacement/revocation affects subsequent lookups, including retries, but
cannot recall an external request or plaintext already acquired by in-flight code.
Do not retain `SettingValue` or captured plaintext in a shared cache, job payload,
log, exception, frontend response or static property. JSON/debug metadata contains
only `configured` and `version`; serialization is refused.

### External HTTP and MCP operations

Resolve `Nexia\Integrations\Contracts\ExternalIntegrations` to execute a registered
operation shared with your App. Core retains the endpoint and credentials; Apps
pass an operation public ID, validated business input, and a stable idempotency key.
The current actor must have permission to use the connection. Matching Core and
container runtime support must be adopted with this SDK contract.

Call `submit()` after your App transaction commits, preferably from durable App
work. Declare `integration.submit` and `integration.result` in that work handler's
`platform_callbacks`. Submission inside an App transaction is rejected because an
App rollback cannot undo a Core receipt or external effect.

```php
$integrations = app(\Nexia\Integrations\Contracts\ExternalIntegrations::class);
$id = $integrations->submit($actor, $operationId, ['order' => $orderReference], $requestKey);
$result = $integrations->result($actor, $id);
```

Submission returns an execution ID, not external success. Results can be `queued`,
`running`, `retrying` (signed webhooks), `succeeded`, `failed`, `cancelled`, or `unknown`; only the original App
and actor with current connection access can retrieve them. Identical submissions
reuse the same execution. Reusing the key with changed input is rejected.
`unknown` means the external effect may have occurred: reconcile with the provider
before explicitly starting another request. Revocation prevents subsequent use;
it cannot recall a request already sent. Do not log returned business data.

## Staged tenant migrations

PHP SDK 0.10.1 accepts an optional `tenant_migration_phases.contract` list in
`nexia.json`. List unique migration basenames without `.php`, for example:

```json
"tenant_migration_phases": {
  "contract": ["2026_10_18_000001_add_document_reference"]
}
```

Use this only with a host and runtime supporting staged migrations. Listed
migrations run after the previous runtime has retired; all other migrations
remain in the preparation phase. The new App must keep its ordinary screens
working until the deferred tables or constraints are ready. The platform binds
the declaration to the submitted migration files and checksums. This declaration
does not authorize a developer to bypass retirement or replay uncertain writes.
Apps without this field retain their existing migration behavior.

### Conditional profile contributions

`SlotWidgetDescriptor` accepts optional `availabilityResourceKey`. For the
Core-owned `profile.team.overview` API-v2 slot, declare an App-owned resolvable
ResourceDescriptor and ResourceReferenceResolutionContribution. The contract
must be discoverable (`publicForIntegration: true`); it may remain excluded
from Builder (`publicForBuilder: false`). Public discovery does not grant data
access. The resolver accepts the actor public ID only with the same actor,
selected Legal Entity and `<app-key>.profile-availability` purpose. Return a
ResolvedResourceReference with only `fields.visible: bool` after independent
permission/population checks. Lookup failures should propagate, not become false.

Requires the matching Core host. The host hides false results and shows retry
on unresolved or invalid responses. The optional field is omitted from legacy
wire declarations when null. Profile props remain v2; `visibility: owner`
describes workspace ownership, never permission to read or grant data for
other people. Every App endpoint must enforce its own subject authorization.


### Shared document data

`Nexia\DocumentData\Contracts\DocumentDataHost` separates metadata discovery
from owner-authorized value resolution. Contributions use
`AppDescriptors\DocumentDataSourceDescriptor` and explicitly declare purposes.
Legacy Signature DTOs/interfaces remain valid; neutral DocumentData DTO names
are aliases of their validated counterparts. The matching Core and native
runtime must be adopted before an App consumes this host: PHP SDK 0.10.10,
Core 0.8.152 and Manager 0.1.0-alpha.75 or later. See the
[document-data reference](https://developers.nexia.to/docs/app-sdk-electronic-signature-contracts)
for stable bindings, pagination and unchanged protected-result limits.

### Grouped Resource Reference resolution

A consumer with multiple effective dates or owner-defined filters can use the
optional `Nexia\ResourceReference\Contracts\GroupedResourceReferences`
capability from PHP SDK 0.10.12. Adopt the matching host/runtime dispatch
before enabling grouped providers. Keep one actor, Legal Entity, operating-unit
context and purpose
per call. Group keys identify results; missing or denied IDs can be omitted.
For example, with an injected `ResourceReferences $references`, an authorized
`ResourceReferenceResolutionContext $context`, and an owner resource key:

```php
$groups = [
    'first-date' => ['resource_ids' => $resourceIds, 'as_of' => $firstDate],
    'second-date' => ['resource_ids' => $resourceIds, 'as_of' => $secondDate],
];

if ($references instanceof \Nexia\ResourceReference\Contracts\GroupedResourceReferences) {
    $resolved = $references->resolveManyGrouped($resourceKey, $groups, $context);
} else {
    $resolved = [];
    foreach ($groups as $key => $group) {
        $groupContext = new \Nexia\ResourceReference\ResourceReferenceResolutionContext(
            legalEntity: $context->legalEntity,
            actor: $context->actor,
            asOf: $group['as_of'],
            purpose: $context->purpose,
            operatingUnit: $context->operatingUnit,
            includeProtectedValues: $context->includeProtectedValues,
            effectiveThrough: $group['effective_through'] ?? $group['as_of'],
            filters: $group['filters'] ?? $context->filters,
            sort: $context->sort,
        );
        $resolved[$key] = $references->resolveMany(
            $resourceKey, array_values(array_unique($group['resource_ids'])), $groupContext,
        );
    }
}
```

`$firstDate` and `$secondDate` are `DateTimeImmutable` values. Consume results
with `$resolved[$groupKey][$resourceId] ?? null`; never retry omitted IDs through
an unscoped lookup. The transport splits requests into at most 31 groups and
400 total selectors, with at most 100 unique IDs per group fragment. Bound the
consumer's input window too: transport splitting does not bound the entire
input collection or an owner's in-memory result size.

Owners opt in explicitly by implementing
`Nexia\ResourceReference\Contracts\ResourceReferenceGroupedResolutionContribution`,
which extends `ResourceReferenceBatchResolutionContribution`. Its
`resolveManyGrouped(array $groups, ResourceReferenceResolutionContext $context)`
returns `array<group-key, array<resource-id, ResolvedResourceReference>>`.
Register the same resolver contribution as before; implement the inherited
single/batch paths as well. Deploy the compatible SDK and host/runtime dispatch
before adopting this interface. The matching runtime calls the grouped owner
once per bounded request; owners without this capability keep the existing
per-group batch/single resolution fallback. A grouped HTTP call alone therefore
does not prove that owner SQL is batched.

Inside the grouped owner, read relevant local rows and relations once for the
bounded selector set, then evaluate each group's exact date, filters and
`effective_through`. Preserve the owner's interval boundaries (including any
exclusive end date), timezone and assignment precedence. A broad min/max date
query only supplies candidates; it must not substitute one date's result for
another's. Return each reference with its group's exact `asOf` and enforce live
actor, organization, purpose and record authorization for every group. Reusing
loaded models within this invocation is allowed; caching authorization across
requests or treating a denied reference as permission to read a projection is
not.

Measure both sides of this boundary in focused tests. Use an isolated App
fixture with repeated IDs across dates and units, count outgoing host calls and
owner SQL separately, and assert a query budget that does not multiply by
rows × dates × units. Compare small and large inputs, first/last effective-day
boundaries, denied actors, separate invocations after data changes, and legacy
fallback results. Record elapsed time and peak memory without logging IDs,
payloads or tokens. Finally time the actual authorized workflow, including file
reading and result preparation; a mocked transport benchmark is not end-to-end
performance evidence.

## Optional custom read batching

A read-only JSON GET controller method may declare `#[Nexia\AppRuntime\BatchableRead]`.
The catalog exporter discovers its registered routes; the existing SDK HTTP API
continues to work without declaration. See [read batching](docs/read-batching.md)
for authorization boundaries, non-blocking development info and rollout order.

## Released optional contracts

Core 0.8.173 supports the contracts below with a matching App runtime.
Apps must adopt the required PHP package and keep actor/record authorization.
SDK installation alone does not enable an App workflow.

| Contract | PHP SDK | Procedure |
| --- | --- | --- |
| `PersonLegalEntityAccess::end` | 0.10.13 | [Company access termination](https://developers.nexia.to/en/docs/app-sdk-sdk-contracts) |
| `ApprovalSubmissionHost` | 0.10.15 | [Background submission](https://developers.nexia.to/en/docs/app-sdk-approval-contracts) |
| `PersonProfileHistory::pageForAuthorizedHr` owner reference | 0.10.15 | [Authorized profile history](https://developers.nexia.to/en/docs/app-sdk-sdk-contracts) |
| `IncrementalResourceImportPipeline` and `resultRowsPath` | 0.10.16 | [Resumable imports](https://developers.nexia.to/en/docs/building-apps-import-and-export) |

Implementations of `PersonProfileHistory` must accept the final optional
`?ResourceRef $authorizationResource = null` parameter. An owner reference is
not a permission grant; callers omitting it still need directory authority.
Approval receipts are not completed cases, and pending import checkpoints are
not eligible batches. Keep uncertain results under their original request keys.
