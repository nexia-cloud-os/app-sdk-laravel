---
status: published
version: 0.10.32
date: 2026-11-14
title: 선택적 ID 일괄 조회와 완전한 기간 조회
description: ID 일괄 조회와 완전한 유효 기간 참조 계약을 추가합니다.
---

호환 호스트는 `BatchActorDirectory`와 `BatchCanonicalPartyDirectory`로 조회 가능한 ID를 일괄 조회할 수 있습니다. 선택적 인터페이스를 확인하고 기존 단건 조회 대체 경로를 유지하세요. [기본 정보 참조](https://developers.nexia.to/ko/docs/building-apps-core-database-references)와 [SDK 안내](https://github.com/nexia-cloud-os/app-sdk-laravel#optional-batch-identity-and-complete-range-queries)를 참고하세요.

소비 앱은 `CompleteEffectiveRangeReferences`로 제한된 정확 검색어의 권한 있는 전체 결과를 요청할 수 있습니다. 소유 앱은 `ResourceReferenceCompleteEffectiveRangeSearchContribution`을 구현하며, 완전한 결과를 제공할 수 없으면 `null`을 반환합니다. 이때 소비 앱은 기존 조회 경로를 사용해야 합니다. 기간 검색 한도는 최대 100,000건까지 명시할 수 있고 기본값 10,000건과 응답 크기 제한은 유지됩니다. 사용 전에 호환되는 Core와 앱 런타임을 적용하세요. [리소스 참조](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)와 [SDK 안내](https://github.com/nexia-cloud-os/app-sdk-laravel#optional-batch-identity-and-complete-range-queries)를 참고하세요.
