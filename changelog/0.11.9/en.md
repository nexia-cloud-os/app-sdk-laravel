---
status: published
version: 0.11.9
date: 2026-11-26
title: App operation failure reporting
description: Report handled App write failures to a compatible Core.
---

- Use `Nexia\AppRuntime\OperationFailure::with()` to report a handled write failure alongside the normal JSON result. A compatible Core displays the operation, stage, reason code, and available inner HTTP status and count. [Test an App](https://developers.nexia.to/docs/building-apps-test-an-app)
