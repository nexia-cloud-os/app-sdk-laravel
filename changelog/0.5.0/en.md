---
status: published
version: 0.5.0
date: 2026-09-15
title: Independent Laravel and React SDK packages
description: Replace the combined SDK with separately published Laravel and React packages, canonical contracts.
---

## Changed

- Replaced the combined App SDK with the independently published Laravel package `amuzcorp/nexia-app-sdk-laravel` and React package `@amuzcorp/nexia-app-sdk-react`.
- PHP contracts now use their canonical namespaces. The former combined package identity and legacy namespace bridges are unavailable.

## Added

- Added `NxLinkButton` and `NxIconLink`, route-navigation counterparts to `NxButton` and `NxIconButton`. They accept canonical `href` values and let the Core host provide Work Tab navigation without treating a route change as a command callback.
- `NxStatCard` and `NxOverviewBand` now navigate through canonical `href` values.

## Upgrade required

- Replace the retired combined SDK dependencies and imports with `amuzcorp/nexia-app-sdk-laravel` and `@amuzcorp/nexia-app-sdk-react` at `^0.5.0`.
- Update PHP imports to the canonical contract namespaces. Do not retain legacy package dependencies, aliases, or namespace wrappers.

## Usage

- [Build an App screen](https://developers.nexia.to/docs/building-apps-build-a-frontend-surface)
- [Start using the SDK](https://developers.nexia.to/docs/app-sdk-overview)
