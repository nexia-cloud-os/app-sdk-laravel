---
status: published
version: 0.10.24
date: 2026-11-07
title: Consistent dashboard source reads
description: Declare a required repeatable-read snapshot for dashboard sources.
---

Dashboard sources can declare `repeatableRead: true` when reads require one database snapshot. Adopt a Runtime that supports the declaration before using it, and make the App fail if it cannot establish the required isolation; an older Runtime can ignore the declaration. The snapshot does not cover Core callbacks or separate database connections. See the [snapshot guide](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.24/docs/dashboard-read-snapshot.md).
