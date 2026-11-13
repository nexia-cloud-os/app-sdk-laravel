---
status: published
version: 0.10.30
date: 2026-11-13
title: Legal Entity functional currency
description: Read configured functional currency when supported by the host.
---

Apps can check `HasFunctionalCurrency` for the currency code and minor-unit precision. Both are nullable; do not assume KRW when absent. Adopt a compatible Core and App runtime before relying on this contract. See the [organization contract guide](https://github.com/nexia-cloud-os/app-sdk-laravel#functional-currency).
