---
status: published
version: 0.11.6
date: 2026-11-23
title: Dashboard widget height
description: Declare grid-row sizes for App dashboard widgets.
---

Pass the optional `height` argument to `DashboardWidget` to set the minimum, initial and maximum height in grid rows. An explicit `maxRows: null` leaves the maximum open. A saved widget retains its saved height and uses the declared bounds when resized. Use Core 0.12.1 or later before submitting an App that declares a height.

[Dashboard widget guide](https://developers.nexia.to/en/docs/platform-extensions-add-dashboard-widget#widget-height) · [SDK contract](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.6/src/Dashboard/DashboardWidget.php)
