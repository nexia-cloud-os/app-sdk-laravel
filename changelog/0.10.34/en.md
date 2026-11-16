---
status: published
version: 0.10.34
date: 2026-11-16
title: Paged import decision review
description: Adds an optional contract for paged import review.
---

Apps can implement `PagedImportDecisionProvider` to page review decisions and preserve selections. Bind selections and operation receipts to the actor, tenant, batch and analysis generation, save them atomically, and verify the sealed selection on import. Adopt matching Core and App runtime support before using this contract. Existing non-paged imports remain supported.
