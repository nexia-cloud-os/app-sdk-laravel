---
status: published
version: 0.7.1
date: 2026-10-01
title: Shared Composer and sandbox SDK
description: Shared Composer and sandbox SDK
---

- Clarify that `StoredFile.disk` and `path` are empty in isolated runtimes. Use `AuthorizedFileReader` for authorized bytes and `PdfFontProvider` for a font local to the active runtime; a Core storage path is not an App filesystem API.
- Add `OfficialSealUseDescriptor` for an exact App rendering purpose and its existing permission keys. Publish it through `AppDescriptorContribution`; the matching Core checks current grants and records actor-bound seal use. The descriptor grants no permission itself. Adopt the SDK, Core callback and isolated runtime together before using it.
## Added

- Public `TenantAppInitializerContribution`, `VersionedTenantAppInitializerContribution` and immutable `TenantAppInitializationContext` support App-owned required installation defaults without importing Core. The matching Core installer discovers contributions, checks App ownership and tracks completion. Context exposes definition, tenantId and initializationVersion, not a Core model. Initializers must be idempotent and avoid external side effects.
- Optional `ResourceDescriptor::publicForIntegration` controls developer contract discovery independently of Builder visibility. Omission retains the existing visibility. This metadata flag grants no runtime access.
- `ResourceDescriptor::integrationContract()` provides the shared discovery projection for installed and isolated Apps. Internal or removed resources return `null`; only composition-public events are included. It does not expose implementation routes or grant read or mutation authority. Adopt the matching Core and sandbox runtime adapters to discover selected development Apps.
- A database-only Laravel host can pass `null` as the identity resolver to `ScoutSearchResolverRegistry::configure`, while still supplying a real Scout engine resolver. Existing hosts keep their identity resolver unchanged. This does not enable shared tenant search.

## Adoption

Adopt the matching Core initializer/discovery changes and its tenant migration `2026_09_23_060000_track_app_initialization_application`. Existing App manifests and ResourceDescriptor calls remain valid. Host-internal initializer implementations move to the SDK interfaces and scalar context.

## App request middleware

- Generated App routes use the public `Nexia\Http\Middleware::APP_REQUEST` entry instead of importing host tenancy middleware. Core maps it to the existing web, tenancy, session, authentication and delegation chain; App installation and resource context checks remain in place. Adopt the matching SDK/Core release before generating these routes. Isolated runtime request authentication is a separate adapter requirement.

- Isolated App catalogs now carry decision-result templates, including typed result fields and optional DMN seeds, through `PlatformDescriptorWire`. Adopt the matching sandbox runtime to expose these templates in Core authoring without installing App PHP in Core.

## Standard Runtime declarations

- Add versioned Runtime requirements, declaration capabilities, definition-only app/tenant settings and an explicit unavailable Action execution contract. Unsupported required capabilities and integrations are rejected; these declarations do not enable settings access, asynchronous work or state changes.

## Composer compatibility

The canonical PHP package is `nexia/sdk-laravel`; it replaces `amuzcorp/nexia-app-sdk-laravel` at the same version. Update the root requirement to the canonical name; compatible App requirements remain valid. This does not widen App constraints or update existing locks automatically. `NexiaModel` retains media interfaces for released Apps: Composer hosts explicitly enable local media, while isolated runtimes keep it disabled. Adopt the matching Core and sandbox-manager adapters before using new runtime capabilities.
- Add the shared dependency policy and registered collection-route inspection. Runtime packages and the operator test toolchain are separate allowlists; Apps cannot introduce production packages, executable autoload hooks or another App dependency.
- Adopt the matching Core and sandbox-manager source together. The existing published SDK 0.7.0 does not contain these draft contracts; publish and adopt a new compatible SDK artifact before building the release Runtime.

- Shared metadata reading validates the declaration shape without selecting a sandbox host. Sandbox entry points still reject unsupported Runtime versions, required capabilities and integrations before loading App PHP. Existing Composer metadata without `platform` keeps its parsing behavior; installing and booting production Apps remains a separate compatibility check.
