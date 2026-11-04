---
status: published
version: 0.10.20
date: 2026-11-04
title: Business API permission declarations
description: Declare additional permissions checked by business API handlers.
---

App handlers can declare always-required additional permissions with `BusinessApiPermissions`. Adopt the matching Core receiver and Manager runtime before relying on role previews; conditional checks and authorization remain the App’s responsibility. See the [Developer API guide](https://developers.nexia.to/docs/building-apps-add-api-and-authorization) and [SDK contract](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.20/src/AppRuntime/BusinessApiPermissions.php).
