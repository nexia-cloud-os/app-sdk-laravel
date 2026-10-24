---
status: published
version: 0.10.10
date: 2026-10-24
title: Shared document data and profile availability
description: App-neutral document data contracts and conditional profile contributions.
---

- Reuse document data catalogs and resolution outside electronic signatures through `DocumentDataHost` and provider contracts. Existing signature contracts remain compatible.
- Declare `profile.team.overview` and `availabilityResourceKey` for actor-specific profile visibility; the owning App retains authorization.
- Adopt the matching Core 0.8.152 host and Sandbox Manager 0.1.0-alpha.75 runtime together. Package publication and host dependency adoption are separate steps.
