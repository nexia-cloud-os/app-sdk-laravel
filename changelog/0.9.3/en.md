---
status: published
version: 0.9.3
date: 2026-10-14
title: Registered person and company profiles
description: Read optional birth dates and registered company facts through authorized host contracts.
---

Apps can read an optional ISO birth date from `PersonProfileView` and registered company facts through `HasLegalEntityProfile::legalProfile()`. The company profile contains the legal name, business number, representative and registered address. Existing constructor calls remain compatible; absent values remain null.

Adopt the matching Core implementation, nullable tenant migration and isolated runtime adapter before using these fields. Existing issued documents are not rewritten.
