---
status: published
version: 0.10.2
date: 2026-10-19
title: Automatic batched reference resolution
description: Large reference requests are split into bounded batches.
---

`ResourceReferences::resolveMany()` validates and deduplicates IDs, then sends batches of at most 100 IDs. Existing callers do not need their own transport loop. Host authorization still applies to every batch. Existing transport callbacks remain supported.

The SDK 0.10 migration requirements in 0.10.0 still apply when upgrading from 0.9. Existing approved Apps may retain their compatible 0.9 runtime; this release does not require every App to upgrade.
