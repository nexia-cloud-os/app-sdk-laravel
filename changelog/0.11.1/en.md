---
status: published
version: 0.11.1
date: 2026-11-18
title: Installed App work concurrency
description: Optional declarations for independent jobs and read-only import planning.
---

Import pipelines can declare `supportsReadOnlyPlan` after verifying that the first plan read and its review pages are free of writes and row locks. Adoption requires a compatible SDK, Core and App runtime. Existing declarations continue to work. [Import and export](https://developers.nexia.to/en/docs/building-apps-import-and-export).

App work and import pipeline definitions can declare `workConflictGroup` to identify operations that must run in sequence. Check shared writes and external effects before assigning distinct groups. Existing declarations retain their conservative scheduling. This requires a compatible SDK, Core and App runtime. [Import and export](https://developers.nexia.to/en/docs/building-apps-import-and-export).
