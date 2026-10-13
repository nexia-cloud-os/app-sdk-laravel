---
status: published
version: 0.9.1
date: 2026-10-13
title: External database writer network access
description: Register source IPs through a shared, revision-checked SDK contract.
---

## Added

- Add the optional `ExternalDatabaseWriterNetworkAccess` contract to read and replace a writer's allowed public IPv4 addresses. Updates require the last read revision; an empty list revokes access. Responses distinguish saved policy from applied policy.

## Adoption

- Requires the paired Core and isolated-runtime implementations before use. Existing writer provisioning remains unchanged. Display network application status before exposing connection details; the browser IP may differ from the database client's egress IP.
