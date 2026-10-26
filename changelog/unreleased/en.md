---
status: draft
---


- Slot widget component keys now accept hyphenated App identifiers in the sandbox catalog, matching Composer registrations. Component paths, URLs, and PHP class names remain invalid.
- Resource subject actor declarations and signed approval callbacks now share the SDK wire contract, allowing sandbox hosts to resolve the owning App’s approval subject.

- `ResolvedResourceReference::fromSnapshot()` restores public snapshots with hash validation and rejects protected values. Write-precondition and selector documentation now distinguishes local transaction locks from isolated completion checks without promising cross-runtime atomic rollback.

- Performance measurement descriptors now round-trip through the shared platform wire. Removed `PartyDirectory::requireByPublicIdForUpdate`, `OrganizationDirectory::lockLegalEntityByKey` and `lockOperatingUnit`. Use the shared write-precondition or declared reference-selector contracts; these do not provide a joint Core/App transaction.

- Apps can declare owned asynchronous work handlers and schedules through the shared SDK catalog. The host owns installation, authorization, delivery, retry, and deployment-generation validation; handlers receive only a validated invocation. Adopt the matching Core queue and scheduler integration before declaring production work.

- App metadata now requires `nexia.json` schema version `2` with runtime `laravel`; Composer `extra.nexia` declarations and duplicate declarations are rejected. Require `nexia-cloud-os/sdk-laravel`; the old package-name replacements are removed. Adopt the matching Core and runtime loaders together.
- Removed the PHP `ResourceActionDescriptor` alias. Declare catalog actions with `ActionDefinition`; the React dropdown action type is unaffected.

- The optional `SignatureBulkBindingLifecycleProvider` contract lets a bulk-signature provider reconcile reservations and accepted or released requests. [Lifecycle implementation guide](https://developers.nexia.to/docs/platform-extensions-electronic-signature).

## Conditional My Team profile contributions

- Add the API-v2 `profile.team.overview` placement and optional `SlotWidgetDescriptor.availabilityResourceKey`. The owning App resolves actor eligibility through a same-owner Resource Reference returning `fields.visible`. Failed lookups show retry; existing personal slots and null-field wire shapes remain compatible. Adopt the matching PHP SDK and Core host before registering the new slot. Permission and subject checks remain mandatory in the App.


## Shared document data

PHP adds `DocumentDataSourceDescriptor`, `DocumentDataSourceContribution` and
`DocumentDataHost` for purpose-scoped document preparation. Neutral query/result
names reuse the existing validated value contract. Existing Signature sources
remain Signature-only unless explicitly opted in. Save exact source/version/field
bindings and adopt matching Core/runtime support before consuming the new host.
Release versions are pending. See the
[contract guide](https://developers.nexia.to/docs/app-sdk-electronic-signature-contracts).

## Grouped Resource Reference owner resolution

Owners may optionally implement `ResourceReferenceGroupedResolutionContribution` to resolve multiple dates and filter groups in one call on a runtime supporting this capability. Existing batch providers remain compatible and retain per-group resolution. Authorize every group using its date and filters under the supplied actor and organization context; omit denied references and retain exact group keys and as-of times.
