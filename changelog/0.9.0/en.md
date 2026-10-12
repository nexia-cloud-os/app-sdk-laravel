---
status: published
version: 0.9.0
date: 2026-10-12
title: Explicit process document targets
description: Process starts identify an authored document and one executable process.
---

## Upgrade required

- `ProcessStartBindingDescriptor` requires `documentKey` instead of `definitionKey`. Update named arguments and serialized catalogs; the old field is rejected. Pair it with `processId` to select the executable process.
- Host test fixtures create the document through `createBpmnDocument()` before calling `createProcessDefinition()` with `bpmn_document_id` and `bpmn_process_id`.
- Requires the matching Core 0.8.0 implementation and updated consuming Apps. Rebuild isolated App artifacts and reinstall their catalogs before activation.
