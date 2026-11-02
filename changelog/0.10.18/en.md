---
status: published
version: 0.10.18
date: 2026-11-02
title: Large effective-range reference queries
description: Resolve more than 100 reference selectors through the existing SDK method.
---

PHP SDK 0.10.18 fixes effective-range searches that failed when callers supplied more than 100 selectors. `ResourceReferences::searchEffectiveRange()` keeps its existing signature and result limit, preserves references at distinct effective dates, and reports a failed search instead of returning an incomplete result when a required request fails. Existing callers remain compatible; no migration is required.

See the [resource reference guide](https://developers.nexia.to/docs/platform-extensions-resource-references) and [published implementation](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.18/src/ResourceReference/TransportResourceReferences.php). Package installation and adoption by a running App are separate steps.
