---
status: published
version: 0.10.18
date: 2026-11-02
title: 대량 유효기간 참조 조회 수정
description: 기존 SDK 메서드로 100개를 넘는 참조 검색어를 조회할 수 있습니다.
---

PHP SDK 0.10.18은 검색어가 100개를 넘으면 유효기간 참조 조회가 실패하던 문제를 수정합니다. `ResourceReferences::searchEffectiveRange()`의 메서드 형식과 결과 개수 제한은 유지됩니다. 유효일이 다른 참조는 구분해 반환하며, 필요한 요청이 실패하면 불완전한 결과를 반환하지 않고 조회 실패를 알립니다. 기존 호출과 호환되며 마이그레이션은 필요하지 않습니다.

[다른 앱의 레코드 참조하기](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)와 [공개 구현](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.18/src/ResourceReference/TransportResourceReferences.php)을 참고하세요. 패키지 설치와 실행 중인 App의 채택은 별도 단계입니다.
