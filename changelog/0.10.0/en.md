---
status: published
version: 0.10.0
date: 2026-10-16
title: Independent App test boundaries
description: Independent App test boundaries
---

Removed the public test-only `Nexia\Laravel\Testing\Contracts\HostTestModelStore`, `Nexia\Laravel\Testing\HostTestStore` and `Nexia\Testing\Contracts\HostTestStore` contracts. Apps adopting PHP SDK 0.10 must replace shared Core model fixtures with standalone App-owned Pest fixtures and the public host contracts. Run `nexia test` (or `nexia test --database` for persistence) and verify the actual runtime separately. Core model access and embedded Composer App execution are not restored.

Existing installed Apps may retain their approved PHP SDK 0.9 artifacts and matching runtime. This release does not require every App to upgrade or change its public version. Runtime production API signatures are unchanged from 0.9.4; the minor version marks removal of the public test seam.
