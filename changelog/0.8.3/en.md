---
status: published
version: 0.8.3
date: 2026-10-05
title: Scheduled work catalog persistence
description: Validate schedule fields independently of JSON object key order.
---

# Laravel SDK 0.8.3

## Scheduled work catalog persistence

- Accept schedule objects regardless of JSON field order while continuing to reject missing and unexpected fields. This prevents valid scheduled Apps from failing after PostgreSQL JSON storage. Adopt with Core catalog canonicalization and rebuild affected runtime artifacts.

## Usage

- [Run background and scheduled work](https://developers.nexia.to/docs/building-apps-background-work)
- [Start using the SDK](https://developers.nexia.to/docs/app-sdk-overview)
