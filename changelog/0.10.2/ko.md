---
status: published
version: 0.10.2
date: 2026-10-19
title: 참조 일괄 조회 자동 분할
description: 큰 참조 조회 요청을 제한된 크기로 나누어 전송합니다.
---

`ResourceReferences::resolveMany()`는 ID를 검증하고 중복을 제거한 뒤 최대 100개씩 나누어 전송합니다. 기존 호출부에서 전송 반복문을 따로 작성할 필요가 없습니다. 각 요청에는 기존 호스트 권한 검사가 적용되며 기존 전송 callback도 지원합니다.

0.9에서 업데이트할 때는 0.10.0의 변경 안내가 계속 적용됩니다. 기존 승인 앱은 호환되는 0.9 런타임을 유지할 수 있으며, 모든 앱이 이번 버전으로 업데이트할 필요는 없습니다.

## 사용 방법

- [다른 앱의 레코드 참조하기](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)
