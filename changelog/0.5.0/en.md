---
status: published
version: 0.5.0
date: 2026-09-15
title: Independent Laravel and React SDK packages
description: Replace the combined SDK with separately published Laravel and React packages, canonical contracts, and an append-only wire catalog.
---

## Changed

- Replaced the combined App SDK with the independently published Laravel package `amuzcorp/nexia-app-sdk-laravel` and React package `@amuzcorp/nexia-app-sdk-react`.
- PHP contracts now use their canonical namespaces. The former combined package identity and legacy namespace bridges are unavailable.
- Added a private, versioned wire catalog and package-local fixtures for the existing PHP and React vocabularies. Wire catalog v1 is append-only; a removal, rename, or semantic change requires a new catalog version and coordinated consumer adoption.

## Added

- Added `NxLinkButton` and `NxIconLink`, route-navigation counterparts to `NxButton` and `NxIconButton`. They accept canonical `href` values and let the Core host provide Work Tab navigation without treating a route change as a command callback.
- `NxStatCard` and `NxOverviewBand` now navigate through canonical `href` values.

## Upgrade required

- Replace the retired combined SDK dependencies and imports with `amuzcorp/nexia-app-sdk-laravel` and `@amuzcorp/nexia-app-sdk-react` at `^0.5.0`.
- Update PHP imports to the canonical contract namespaces. Do not retain legacy package dependencies, aliases, or namespace wrappers.
- Adopt both published artifacts together in a clean Composer and npm consumer before updating Core locks. Local path repositories, workspace overrides, and archives are not release evidence.
