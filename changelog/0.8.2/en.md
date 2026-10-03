---
status: published
version: 0.8.2
date: 2026-10-03
title: Host-provided App database connections
description: App database adapters resolve their connection contract through explicit host wiring.
---

# Laravel SDK 0.8.2

App DB and Schema facades and model connection selection now use the host-configured connection resolver, following the existing authorization adapter pattern. They no longer call Laravel runtime globals from the SDK.

Core and Sandbox Manager must configure the resolver during service registration. Unconfigured access fails closed; each call resolves the current host service, preserving tenant and App authorization boundaries. No App source API change is required.
