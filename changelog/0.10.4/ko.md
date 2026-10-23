---
status: published
version: 0.10.4
date: 2026-10-21
title: 묶음 리소스 참조 조회
description: 여러 기준 날짜나 소유 App이 정의한 필터로 참조를 묶어 조회합니다.
---

App은 선택적으로 `GroupedResourceReferences::resolveManyGrouped`를 사용해 여러 기준 날짜나 소유 App이 정의한 컬럼 필터별로 리소스 ID를 묶어 조회할 수 있습니다. 기존 `ResourceReferences` 구현체는 계속 사용할 수 있습니다.

묶음 조회를 사용하기 전에 대응 Core와 격리 런타임 릴리스를 적용해야 합니다. 소유 App은 허용할 필터를 명시적으로 지원해야 하며, 각 묶음의 사용자·조직·소유 App 권한 검사는 유지됩니다.

## 사용 방법

- [다른 앱의 레코드 참조하기](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)
