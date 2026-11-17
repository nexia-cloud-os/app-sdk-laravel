---
status: published
version: 0.11.0
date: 2026-11-17
title: Stopped data migration status
description: Adds a distinct status for user-stopped imports.
---

`DataMigrationRunStatus` includes `Cancelled` for an import stopped by its user. Apps that exhaustively handle run states must handle the new value. A compatible Core host is required to produce it. See [Import and export](https://developers.nexia.to/docs/building-apps-import-and-export). [SDK source](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/main/src/DataMigration/DataMigrationRunStatus.php).
