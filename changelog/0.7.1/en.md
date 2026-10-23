---
status: published
version: 0.7.1
date: 2026-10-01
title: Shared Composer and sandbox SDK
description: Shared Composer and sandbox SDK
---

- In isolated runtimes, `StoredFile.disk` and `path` are empty. Use `AuthorizedFileReader` for bytes and `PdfFontProvider` for runtime-local fonts; Core storage paths are not an App filesystem API.
- Declare `OfficialSealUseDescriptor` through `AppDescriptorContribution` for a rendering purpose and its existing permission keys. The declaration does not grant access.
- `TenantAppInitializerContribution`, `VersionedTenantAppInitializerContribution` and `TenantAppInitializationContext` support required installation defaults. Initializers must be idempotent and avoid external side effects; use SDK interfaces without importing Core models.
- `ResourceDescriptor::publicForIntegration` controls developer discovery independently of Builder visibility. `integrationContract()` excludes internal/removed Resources and grants no data access.
- Generated routes use `Nexia\Http\Middleware::APP_REQUEST`. Do not import Core tenancy middleware. Use a Core/runtime release that implements the declared capabilities.
- Apps can declare decision-result templates, Runtime requirements, settings definitions and unavailable Action execution. Unsupported required capabilities are rejected; declarations alone do not enable settings access, asynchronous work or mutations.
- The PHP package name at this release is `nexia/sdk-laravel`, replacing `amuzcorp/nexia-app-sdk-laravel` at the same version. Update root requirements and locks; App compatibility constraints are not widened. React versions remain independent.
- Apps must follow the supported dependency policy and cannot add executable autoload hooks or direct dependencies on another App. Existing metadata without `platform` remains readable; using new capabilities requires a matching host.

## Usage

- [Start using the SDK](https://developers.nexia.to/docs/app-sdk-overview)
