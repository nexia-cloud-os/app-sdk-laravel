---
status: published
version: 0.6.0
date: 2026-09-16
title: Modern composition and developer contracts
description: Release the current composition contract, notifications, and automatic approval surfaces.
---

## Changed

- SDK distribution moves to GitHub (`nexia-cloud-os/app-sdk-laravel`) for private Composer source and public npmjs for `@amuzcorp/nexia-app-sdk-react`. Existing `0.5.0` artifacts retain their versions and content. Consumers update registry/repository configuration; npm downloads need no token, while private Composer source still requires GitHub access.

- Resource Composition now has one modern `schema_version: 1` wire contract. It requires graph `relationships`, `population`, typed `where` predicates, and the modern result fields; row results additionally require `row_source`.
- React SDK source development supports `pnpm run build:watch` from `packages/react`. It updates the public `dist` exports as source changes, reports type errors, and resumes after corrections. Core can run this watcher with Vite when the local SDK source is selected. Published package names and exports are unchanged.
- `ResourceReferenceResolutionContext` accepts bounded, owner-defined selector filters and sorting so a resource provider can apply them before pagination. Existing constructors remain compatible because the new arguments are optional.

## Removed

- Removed schema-v2 through schema-v5 parsing and the earlier schema-v1 relationship and `filters` shape. Existing saved compositions must be recreated with the modern schema-1 shape after the coordinated environment reset; the SDK does not convert old payloads.

## Added

- Added app-neutral contracts for contributing and publishing business-result notifications from durable events. The implementing Core host owns tenant scope, recipient access, preferences, idempotency and rendering.
- Added automatic-approval fact and outcome contracts. Apps provide current bound resource facts; the Core host controls policy authority and preserves decision evidence without synthesizing a human signature.

## Upgrade required

Use Core 0.6.0 with these Laravel and React 0.6.0 artifacts. Earlier composition payloads are not converted automatically. Recreate affected saved compositions through the coordinated upgrade procedure; do not run a destructive reset implicitly. Review and update consumer compatibility constraints before adoption.
