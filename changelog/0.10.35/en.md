---
status: published
version: 0.10.35
date: 2026-11-16
title: Checkpointed import work
description: Adds an optional checkpoint contract for incremental imports.
---

Incremental import pipelines can implement `WorkCheckpointedResourceImportPipeline` to bind each apply step to the host-provided work identity. Commit the checkpoint and App data together, enforce the original owner and batch, and keep uncertain external writes under review. Adopt compatible Core and Runtime versions before enabling recovery.
