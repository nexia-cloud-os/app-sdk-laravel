---
status: published
version: 0.10.7
date: 2026-10-22
title: Public reference query diagnostics
description: Find invalid public reference query fields during catalog validation.
---

Catalog validation reports missing public descriptor fields, duplicate query keys and invalid paging or resolution declarations through `CatalogValidationException::$issues`. Core can use the same data-only `ResourceQueryDiagnostics::issues()` check. These checks enforce the existing public-reference contract; ordinary model list fields remain separate.

Adopt PHP SDK 0.10.7 in Core and App runtimes with their matching diagnostic consumers, then rebuild runtime images. Devtools 0.2.13 clarifies that source-only checks do not evaluate dynamic catalogs. No React SDK update is required.
