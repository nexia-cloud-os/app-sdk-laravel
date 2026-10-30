---
status: published
version: 0.10.17
date: 2026-10-30
title: Business-form approver authority
description: Declare additional permissions required of business-form approvers.
---

PHP SDK 0.10.17 adds optional `ApprovalFormBindingDescriptor::requiredActorPermissionKeys` for Legal Entity-scoped approval and consultation actor permissions. Existing bindings keep their previous behavior. Adopt the matching Core host and native runtime before declaring these requirements. See the [approval contract guide](https://developers.nexia.to/docs/app-sdk-approval-contracts#restrict-business-form-approvers) and [published descriptor](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.17/src/AppDescriptors/ApprovalFormBindingDescriptor.php).
