---
status: published
version: 0.10.33
date: 2026-11-14
title: 표준 리소스 참조 및 신원 조회
description: 표준 조회 API와 권한을 확인한 보호정보의 정확한 ID 조회를 추가합니다.
---

리소스 참조와 Party·사용자·사람 프로필을 체이닝 방식의 `query()`와 `queryBatch()`로 조회할 수 있습니다. 묶음의 각 항목을 포함해 결과의 `status`, `data`, `page`, `error`를 확인하세요. 동등한 표준 조회가 제공되는 기존 메서드는 전환 기간에 계속 지원하며 deprecated로 안내합니다. 제거 날짜는 정하지 않았습니다.

앱 간 정확한 ID 조회에서 `includeProtectedValues`를 지원합니다. 소유 앱이 사용자·목적·범위에 대한 권한을 확인해야 하며 검색에서는 보호정보를 제공하지 않습니다. 거부된 요청을 보호 조건 없이 재시도하지 마세요.

대응하는 SDK·Core·앱 런타임을 함께 사용하고 [조회 및 전환 안내](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)를 참고하세요.
