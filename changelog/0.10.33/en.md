---
status: published
version: 0.10.33
date: 2026-11-14
title: Standard reference and identity queries
description: Adds standard query APIs and authorized protected exact reads.
---

Apps can use chainable `query()` and `queryBatch()` for Resource References and Party, user and person-profile reads. Check each result’s `status`, `data`, `page` and `error`, including each batch item. Existing equivalent lookup methods remain supported during transition and are deprecated where standard queries are available; no removal date is set.

Cross-App exact ID reads support `includeProtectedValues` when the owner authorizes the actor, purpose and scope. Search does not support protected values. Do not retry a denied request without protection.

Use matching SDK, Core and App runtime versions. See the [query and migration guide](https://developers.nexia.to/docs/platform-extensions-resource-references).
