---
status: published
version: 0.10.4
date: 2026-10-21
title: Grouped resource reference reads
description: Resolve references for several dates or owner-defined filters in bounded requests.
---

Apps can optionally use `GroupedResourceReferences::resolveManyGrouped` to resolve resource IDs under several as-of dates or owner-defined column filters. Existing `ResourceReferences` implementations remain compatible.

Adopt the matching Core and isolated runtime releases before using grouped reads. Resource owners must explicitly support each accepted filter; actor, organization and owner authorization still apply to each group.
