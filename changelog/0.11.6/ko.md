---
status: published
version: 0.11.6
date: 2026-11-23
title: 대시보드 위젯 높이
description: App 대시보드 위젯 높이를 그리드 행 단위로 선언합니다.
---

`DashboardWidget`의 선택 인자 `height`로 최소·기본·최대 높이를 그리드 행 단위로 지정할 수 있습니다. `maxRows`를 `null`로 지정하면 위젯별 상한 없이 늘릴 수 있습니다. 저장된 위젯은 기존 높이를 유지하고 크기를 조절할 때 선언한 범위를 적용합니다. 높이를 선언한 App을 제출하기 전에 Core 0.12.1 이상을 적용하세요.

[대시보드 위젯 가이드](https://developers.nexia.to/ko/docs/platform-extensions-add-dashboard-widget#widget-height) · [SDK 계약](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.6/src/Dashboard/DashboardWidget.php)
