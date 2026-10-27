---
status: published
version: 0.10.15
date: 2026-10-28
title: Approval submission receipts and scoped profile history
description: Accept approval work durably and authorize profile history against an owning resource.
---

PHP SDK 0.10.15 adds the optional `ApprovalSubmissionHost` contract. It returns an `ApprovalSubmissionReceipt` when the host accepts a bound approval for later processing; the receipt is not an Approval Case or a completed submission. The App's declared finalization work handles `commit` or `release` idempotently. Existing synchronous `ApprovalHost` calls remain available. Adopt matching Core and sandbox runtime support before enabling asynchronous submission. See the [approval contract guide](https://developers.nexia.to/docs/app-sdk-approval-contracts).

`PersonProfileHistory::pageForAuthorizedHr()` now accepts an optional owner `ResourceRef` for record-scoped authorization. Apps implementing this interface directly must add the optional parameter. Calls that omit it continue to require directory authority. See the [SDK contract guide](https://developers.nexia.to/docs/app-sdk-sdk-contracts).
