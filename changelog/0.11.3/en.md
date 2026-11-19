---
status: published
version: 0.11.3
date: 2026-11-19
title: Query directory rows and approvals
description: Use Eloquent for directory reads and an authorized query for approval lists and details.
---

Use the read-only PHP SDK models for Party, User, Legal Entity, Operating Unit and Site. Add Eloquent filters, sorting and joins on a compatible managed App database. A developer-local database needs the directory snapshot prepared by the compatible CLI. [Directory model guide](https://developers.nexia.to/docs/building-apps-core-database-references) · [PHP SDK directory models](https://github.com/nexia-cloud-os/app-sdk-laravel/tree/v0.11.3/src/Laravel/Directory)

Use `Approval::query($legalEntity)->paginate()` for lists and `->find($publicId)` for a detail view. For a filter published by the owning App, pass the resource key to `throughResource()`. Use `ApprovalHost::caseSummary()` for outcome reconciliation. [Approval contracts](https://developers.nexia.to/docs/app-sdk-approval-contracts) · [PHP SDK Approval source](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.3/src/Approval/Approval.php)

`IdentityQueries` is deprecated. Existing calls continue to work, including batch results. Use the directory models for new Party and User reads and `PersonProfileDirectory` for profile reads. Adopt a compatible Core and App runtime before using the new calls.
