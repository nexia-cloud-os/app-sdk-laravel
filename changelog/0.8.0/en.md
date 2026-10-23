---
status: published
version: 0.8.0
date: 2026-10-03
title: Shared native App contracts
description: Native v2 Apps, isolated data contracts and host-governed actions.
---

# Laravel SDK 0.8.0

## Required upgrade
- Requires the matching Core 0.7.0/runtime implementation. This is a breaking 0.x release: Apps use `nexia.json` v2 with `runtime: laravel` and the canonical `nexia-cloud-os/sdk-laravel` package. Legacy Composer package-name replacements and `extra.nexia` manifests are removed.
- Replace the removed PHP `ResourceActionDescriptor` alias with `ActionDefinition`. Replace removed Party/Organization locking methods with explicit write-precondition or reference-selector contracts. These do not create a shared Core/App transaction.

## Contracts
- Add App-scoped database connections, external-writer provisioning, asynchronous work and schedule catalogs, versioned synchronous actions, and scoped Backend settings.
- Preserve validated reference snapshots, import-file capabilities and transfer schema/model declarations across runtime boundaries. Hyphenated App widget identifiers and signed approval/subject contracts work through the shared catalog.

## Usage

- [Start using the SDK](https://developers.nexia.to/docs/app-sdk-overview)
