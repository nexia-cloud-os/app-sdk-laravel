---
status: published
version: 0.10.16
date: 2026-10-29
title: Resumable import contracts
description: Declare bounded import checkpoints and authorized result pages.
---

PHP SDK 0.10.16 adds the optional `IncrementalResourceImportPipeline`, `SeekableResourceImportRowSource` and `ImportPipelineStep` contracts. Existing synchronous pipelines remain supported. Implement `validateNext()` and `applyNext()` to commit each bounded unit and its cursor atomically; `slice(offset, limit)` uses zero-based data-row positions and preserves original row keys. A pending checkpoint is not a validated or apply-eligible batch. Recheck actor and organization authority on every invocation, detect duplicates across the full source and avoid repeating committed writes.

A pipeline can declare `resultRowsPath` for authorized paginated results. `skipped` means excluded without saving; `not_processed` means not committed after interruption. Keep both distinct from `unchanged`, and count only committed rows as created or updated. Upload limits are unchanged.

These contracts require the matching Core import consumer/migrations and Manager runtime before use; installing this SDK alone does not enable the workflow. See the [import/export guide](https://developers.nexia.to/docs/building-apps-import-and-export) and the [published contract source](https://github.com/nexia-cloud-os/app-sdk-laravel/tree/v0.10.16/src/ResourceImport).
