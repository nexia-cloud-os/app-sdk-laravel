---
status: published
version: 0.10.5
date: 2026-10-22
title: Declared external API connections
description: Declare App-owned external API operations for administrator review.
---

Apps can declare external HTTP API operations and webhook receiver settings through `ExternalApiDefinition`. The optional `ExternalIntegrations::submitDeclared` method submits an operation by its declaration key. Declarations do not include credentials or grant access by themselves.

Adopt matching Core and isolated runtime releases and rebuild the App against SDK 0.10.5 before using these contracts. An administrator must review and apply a declaration before an App can use the connection.

## Usage

- [External operations](https://developers.nexia.to/docs/app-sdk-external-integrations)
- [CLI troubleshooting](https://developers.nexia.to/docs/troubleshooting-common-installation-errors)
