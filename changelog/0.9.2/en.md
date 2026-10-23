---
status: published
version: 0.9.2
date: 2026-10-14
title: Governed external operations
description: Submit external HTTP and MCP operations through the host.
---

## Added

- `ExternalIntegrations` lets an App submit registered HTTP/MCP operations and retrieve scoped results without receiving connection credentials.

## Adoption

- Requires matching Core and isolated-runtime support. Submit after the App transaction commits and declare `integration.submit` and `integration.result` for durable App work.
- Reuse the same idempotency key for identical requests. Reconcile an `unknown` external outcome before starting a new request.

## Usage

- [External operations](https://developers.nexia.to/docs/app-sdk-external-integrations)
