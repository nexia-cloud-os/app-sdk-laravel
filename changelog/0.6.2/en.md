---
status: published
version: 0.6.2
date: 2026-09-17
title: Unitless numeric composition measures
description: Accept owner-published numeric measures without a unit.
---

## Fixed

- Resource Composition accepts a `null` unit for non-count numeric measures. Count and distinct-count measures still require `count`.

## Compatibility

- Existing payloads remain valid. No migration is required.
