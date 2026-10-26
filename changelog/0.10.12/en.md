---
status: published
version: 0.10.12
date: 2026-10-26
title: Grouped Resource Reference providers
description: Resolve reference groups through an optional owner contract.
---

Owners can implement `ResourceReferenceGroupedResolutionContribution` to resolve multiple date and filter groups. Existing batch providers remain supported. Each group must retain its exact key and as-of time, and enforce actor, organization and record permissions. Adopt the matching runtime before enabling this optional contract.

See the [Resource References guide](https://developers.nexia.to/docs/platform-extensions-resource-references) and [contract and implementation example](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.12/README.md).
