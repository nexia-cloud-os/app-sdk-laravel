---
status: published
version: 0.10.8
date: 2026-10-22
title: Safe composition metadata for union fields
description: Union field types no longer interrupt scalar composition metadata.
---

- Composition metadata remains available when a Resource descriptor includes union field types. Those fields are excluded from scalar composition queries; existing scalar field eligibility is unchanged.

## Usage

- [Choose a supported report query](https://developers.nexia.to/docs/app-sdk-governed-reports)
