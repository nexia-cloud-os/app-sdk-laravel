---
status: published
version: 0.10.32
date: 2026-11-14
title: Optional batch identity and complete range queries
description: Add bounded identity and complete effective-range reference contracts.
---

`BatchActorDirectory` and `BatchCanonicalPartyDirectory` let compatible hosts resolve visible identities in batches. Detect the optional interfaces and keep the existing single-ID fallback. See [identity references](https://developers.nexia.to/docs/building-apps-core-database-references) and the [SDK guide](https://github.com/nexia-cloud-os/app-sdk-laravel#optional-batch-identity-and-complete-range-queries).

`CompleteEffectiveRangeReferences` lets a consumer request complete authorized results for bounded exact terms. An owner can implement `ResourceReferenceCompleteEffectiveRangeSearchContribution`; `null` means the complete result is unavailable and the consumer must use its existing fallback. Explicit effective-range limits can reach 100,000; the default remains 10,000 and response-size limits still apply. Adopt matching Core and App runtime support before using these capabilities. See [Resource References](https://developers.nexia.to/docs/platform-extensions-resource-references) and the [SDK guide](https://github.com/nexia-cloud-os/app-sdk-laravel#optional-batch-identity-and-complete-range-queries).
