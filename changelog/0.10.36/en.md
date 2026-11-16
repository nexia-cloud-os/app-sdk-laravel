---
status: published
version: 0.10.36
date: 2026-11-16
title: Import review evidence contracts
description: Adds optional contracts for observing import review evidence.
---

Apps can expose read-only evidence for an uncertain import to an authorized host using the optional `OperatorResolvableResourceImportPipeline` and `WorkBoundImportReviewEvidence` contracts. Adopt compatible Core and Runtime versions before declaring the work. These contracts do not themselves authorize terminal closure or retry. See [Import and export](https://developers.nexia.to/docs/building-apps/import-and-export).
