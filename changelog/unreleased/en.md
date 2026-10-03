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
