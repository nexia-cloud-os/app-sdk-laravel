---
status: published
version: 0.8.6
date: 2026-10-10
title: Shared report fields and self-service references
description: New shared report normalization and explicit owner-authorized self-service reference contracts.
---

## Shared report field normalization

- Add `DescriptorCompositionFields` for host-proven descriptor fields, keeping selectable fields, filter-only fields and internal relationship bindings distinct. A physical single-target Resource Reference can be used for joins without making its value selectable. Core and isolated Runtime use the same normalization.
- Adopt the matching Core/Runtime changes and rebuild App artifacts with this SDK. Host authorization, physical-column verification and execution checks remain required; this helper does not grant data access. Existing App domain code is unchanged.

## Owner-admitted self references

- Add `ResourceReferenceSelfServiceContribution` for explicit exact-self purposes with owner-enforced actor and organization filtering. It does not grant ordinary directory access. Adopt with the matching Core and runtime before rebuilding participating Apps.

## Usage

- [Reference another App record](https://developers.nexia.to/docs/platform-extensions-resource-references)
- [Choose a supported report query](https://developers.nexia.to/docs/app-sdk-governed-reports)
