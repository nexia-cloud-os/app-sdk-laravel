---
status: published
version: 0.10.1
date: 2026-10-17
title: Staged tenant migration declarations
description: Declare database changes that must wait for the previous App runtime to retire.
---

PHP SDK 0.10.1 accepts the optional `tenant_migration_phases.contract` list in `nexia.json`. Entries are migration basenames without `.php`. Use it only with a host and runtime supporting staged migrations. The new App must remain usable while deferred database changes are pending. Apps omitting the field keep their existing behavior and do not need to update for this feature.

The test-contract removals documented in 0.10.0 still apply when upgrading from 0.9.
