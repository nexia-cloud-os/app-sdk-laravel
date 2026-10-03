---
status: published
version: 0.8.1
date: 2026-10-03
title: Accurate retired PHP symbol checks
description: Avoid false positives from frontend type assertions in PHP tests.
---

# Laravel SDK 0.8.1

Retired PHP short names are checked in PHP code tokens, excluding strings, comments and inline HTML. Fully qualified retired names remain checked throughout the file. This fixes valid frontend `ResourceActionDescriptor` assertions being reported as retired PHP API usage. Compatible with Core 0.7.0 and React SDK 0.8.0; no App changes are required.
