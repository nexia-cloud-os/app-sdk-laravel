---
status: published
version: 0.10.11
date: 2026-10-24
title: Versioned Laravel seeders
description: Register required App data with ordinary Laravel seeders.
---

Register ordered seed versions with `VersionedTenantAppSeederContribution`. Core 0.8.153 and Manager alpha.76 provide execution and published-history checks; CLI alpha.42 explains retry results. Existing initializers remain supported and existing databases are not converted automatically.

See the [registration and recovery guide](https://developers.nexia.to/docs/platform-extensions-required-installation-content) and [SDK contract](https://github.com/nexia-cloud-os/app-sdk/blob/laravel-v0.10.11/packages/laravel/src/AppRuntime/Contracts/VersionedTenantAppSeederContribution.php).
