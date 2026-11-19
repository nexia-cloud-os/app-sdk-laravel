---
status: published
version: 0.11.2
date: 2026-11-19
title: Tenant-managed App schedules
description: Apps can enable or disable individual schedules for each tenant.
---

Apps can enable or pause individual schedules per tenant using the PHP SDK and the compatible Core scheduler. Adopt both before declaring a managed schedule. [Background work](https://developers.nexia.to/docs/building-apps-background-work) · [PHP SDK source](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/main/src/AsyncWork/AppWorkSchedule.php)

Adopt the compatible SDK in Core and the App runtime, and apply the Core tenant migration before using the declaration.
