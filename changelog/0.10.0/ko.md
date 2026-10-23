---
status: published
version: 0.10.0
date: 2026-10-16
title: 독립 앱 테스트 경계
description: 독립 앱 테스트 경계
---

공개 테스트 전용 `Nexia\Laravel\Testing\Contracts\HostTestModelStore`, `Nexia\Laravel\Testing\HostTestStore`, `Nexia\Testing\Contracts\HostTestStore` 계약을 제거했습니다. PHP SDK 0.10을 채택하는 앱은 Core 모델을 공유하는 fixture를 독립적인 앱 소유 Pest fixture와 공개 호스트 계약으로 교체해야 합니다. `nexia test` 또는 DB 테스트용 `nexia test --database`를 실행하고 실제 런타임도 별도로 검증하세요. Core 모델 접근이나 Composer 앱 실행을 복원하지 않습니다.

기존 설치 앱은 승인된 PHP SDK 0.9 산출물과 호환 런타임을 유지할 수 있습니다. 모든 앱의 SDK나 공개 버전을 변경해야 하는 출시는 아닙니다. 운영 API 시그니처는 0.9.4와 같으며 공개 테스트 계약 제거를 minor 버전으로 구분했습니다.

## 사용 방법

- [App 테스트하기](https://developers.nexia.to/ko/docs/building-apps-test-an-app)
