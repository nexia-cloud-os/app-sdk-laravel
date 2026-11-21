---
status: published
version: 0.11.5
date: 2026-11-22
title: Scoped Resource queries and stored references
description: Declare scoped queries and stored Resource reference targets.
---

Apps can implement `SubjectScopedResourceQuery` to restrict an authorized query by subject population and organization. A declaration alone does not grant access; use a compatible Core and App runtime.

String ID fields can declare `reference_resource_key`, or `accepted_resource_keys` with `target_resource_key_field` when a stored key identifies one of several targets. These declarations add neither selector access nor read authority. Adopt compatible Core before relying on discovery. See [Resource References](https://developers.nexia.to/en/docs/platform-extensions-resource-references) and the [SDK contract](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.5/src/AppDescriptors/ResourceDescriptorContract.php).
