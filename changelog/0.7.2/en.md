---
status: published
version: 0.7.2
date: 2026-10-01
title: Public PHP package distribution
description: Install the PHP SDK from Packagist under its canonical package name.
---

- Publish `nexia-cloud-os/sdk-laravel` with unchanged PHP namespaces. Replace previous SDK package names only at `self.version`; compatible existing App constraints remain valid.
- Isolated dependency validation accepts the canonical SDK’s exact legacy replacements and continues to reject incompatible versions and unrelated replacement providers.
- Update root Composer requirements and resolve fresh locks after publication. Existing 0.6-only Apps are not made compatible with 0.7 by this rename. React npm packages version independently.
- Merging a reviewed source release PR into main runs package checks and publishes a package-only distribution snapshot and tag. Existing tags are never moved.
