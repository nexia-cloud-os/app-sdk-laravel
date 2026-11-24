---
status: published
version: 0.11.7
date: 2026-11-24
title: Current approval submission
description: Submit a draft against the currently published approval route and template.
---

Apps can use the optional `CurrentApprovalSubmissionHost` on supported composer requests. Check `availableFor()` and freeze the draft and receipt together. The declared finalizer receives the resolved case, route, template, binding and line through `ApprovalSubmissionDelivery::currentResult` on commit. Retain the synchronous path for hosts without the capability. Adoption requires the matching Core and App Runtime releases. See [Electronic Approval contracts](https://developers.nexia.to/docs/app-sdk-approval-contracts).

[SDK source](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.7/src/Approval/Contracts/CurrentApprovalSubmissionHost.php)
