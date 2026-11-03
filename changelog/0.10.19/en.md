---
status: published
version: 0.10.19
date: 2026-11-03
title: Party business roles and relationships
description: Search Party roles and read authorized business relationships.
---

Laravel SDK 0.10.19 adds actor-authorized Party role searches, batch role and directed relationship reads, and role and relationship write preconditions. Existing calls remain supported. Adopt the matching Core and runtime implementations before using the new contracts; registered preconditions do not themselves confirm a completed write. See the [Core database reference guide](https://developers.nexia.to/docs/building-apps-core-database-references) and [SDK contracts](https://github.com/nexia-cloud-os/app-sdk-laravel/tree/main/src/Identity/Contracts).
