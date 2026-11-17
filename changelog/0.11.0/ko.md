---
status: published
version: 0.11.0
date: 2026-11-17
title: 중단된 데이터 마이그레이션 상태
description: 사용자가 중단한 가져오기에 별도 상태를 추가합니다.
---

사용자가 가져오기를 중단하면 `DataMigrationRunStatus`의 `Cancelled` 상태로 기록할 수 있습니다. 실행 상태를 빠짐없이 분기하는 앱은 이 값을 처리해야 합니다. 실제 상태 기록에는 호환 Core 호스트가 필요합니다. [가져오기 및 내보내기](https://developers.nexia.to/ko/docs/building-apps-import-and-export)를 참고하세요. [SDK 소스](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/main/src/DataMigration/DataMigrationRunStatus.php).
