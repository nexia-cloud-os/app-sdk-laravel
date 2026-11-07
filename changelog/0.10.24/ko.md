---
status: published
version: 0.10.24
date: 2026-11-07
title: 대시보드 소스의 일관된 조회
description: 대시보드 소스에 repeatable-read 스냅샷 요구사항을 선언합니다.
---

대시보드 조회에 하나의 데이터베이스 스냅샷이 필요하면 `repeatableRead: true`를 선언할 수 있습니다. 사용 전에 이 선언을 지원하는 런타임을 적용하고, 필요한 격리 수준을 확보할 수 없으면 앱이 실패하도록 구현하세요. 이전 런타임은 선언을 무시할 수 있습니다. 스냅샷은 Core 콜백이나 별도 데이터베이스 연결에는 적용되지 않습니다. [스냅샷 안내](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.24/docs/dashboard-read-snapshot.md)를 참고하세요.
