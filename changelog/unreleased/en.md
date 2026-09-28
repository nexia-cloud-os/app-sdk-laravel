---
status: draft
---

## Added

- Public `TenantAppInitializerContribution`, `VersionedTenantAppInitializerContribution` and immutable `TenantAppInitializationContext` support App-owned required installation defaults without importing Core. The matching Core installer discovers contributions, checks App ownership and tracks completion. Context exposes definition, tenantId and initializationVersion, not a Core model. Initializers must be idempotent and avoid external side effects.
- Optional `ResourceDescriptor::publicForIntegration` controls developer contract discovery independently of Builder visibility. Omission retains the existing visibility. This metadata flag grants no runtime access.
- `ResourceDescriptor::integrationContract()` provides the shared discovery projection for installed and isolated Apps. Internal or removed resources return `null`; only composition-public events are included. It does not expose implementation routes or grant read or mutation authority. Adopt the matching Core and sandbox runtime adapters to discover selected development Apps.
- A database-only Laravel host can pass `null` as the identity resolver to `ScoutSearchResolverRegistry::configure`, while still supplying a real Scout engine resolver. Existing hosts keep their identity resolver unchanged. This does not enable shared tenant search.

## Adoption

Adopt the matching Core initializer/discovery changes and its tenant migration `2026_09_23_060000_track_app_initialization_application`. Existing App manifests and ResourceDescriptor calls remain valid. Host-internal initializer implementations move to the SDK interfaces and scalar context.

## App request middleware

- Generated App routes use the public `Nexia\Http\Middleware::APP_REQUEST` entry instead of importing host tenancy middleware. Core maps it to the existing web, tenancy, session, authentication and delegation chain; App installation and resource context checks remain in place. Adopt the matching SDK/Core release before generating these routes. Isolated runtime request authentication is a separate adapter requirement.
