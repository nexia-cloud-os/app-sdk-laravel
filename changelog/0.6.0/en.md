---
status: published
version: 0.6.0
date: 2026-09-17
title: Modern Resource Composition and workflow contracts
description: Introduce the current Resource Composition wire contract and app-neutral notification and automatic-approval contracts.
---

## Changed

- Resource Composition numeric measures may preserve an owner-published unit of JSON `null`; count and distinct-count measures still require the `count` unit.
- Resource Composition now has one modern `schema_version: 1` wire contract. It requires graph `relationships`, `population`, typed `where` predicates, and the modern result fields; row results additionally require `row_source`.
- `ResourceReferenceResolutionContext` accepts bounded, owner-defined selector filters and sorting so a resource provider can apply them before pagination. Existing constructors remain compatible because the new arguments are optional.

## Removed

- Removed schema-v2 through schema-v5 parsing and the earlier schema-v1 relationship and `filters` shape. Existing saved compositions must be recreated with the modern schema-1 shape after the coordinated environment reset; the SDK does not convert old payloads.

## Added

- Added app-neutral contracts for contributing and publishing business-result notifications from durable events. The implementing Core host owns tenant scope, recipient access, preferences, idempotency and rendering.
- Added automatic-approval fact and outcome contracts. Apps provide current bound resource facts; the Core host controls policy authority and preserves decision evidence without synthesizing a human signature.

## Upgrade required

- Update every saved Resource Composition payload to the current `schema_version: 1` shape before it is read. Old schema-v1 through schema-v5 payloads are rejected and must not be sent to the SDK.
- Publish and adopt the Laravel SDK 0.6.0 artifact in a clean Composer consumer before updating Core locks. Local source checkouts and path repositories are not release evidence.
