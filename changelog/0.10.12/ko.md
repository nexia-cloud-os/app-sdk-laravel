---
status: published
version: 0.10.12
date: 2026-10-26
title: 리소스 참조 제공자의 그룹 조회
description: 선택 계약으로 날짜와 필터별 참조 그룹을 조회합니다.
---

제공자는 `ResourceReferenceGroupedResolutionContribution`을 구현해 여러 날짜와 필터 그룹을 조회할 수 있습니다. 기존 일괄 조회 제공자는 계속 지원합니다. 각 그룹의 키와 기준 시각을 유지하고 사용자·조직·레코드 권한을 검증해야 합니다. 이 선택 계약을 사용하기 전에 대응 런타임을 적용하세요.

[리소스 참조 가이드](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)와 [계약 및 구현 예제](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.12/README.md)를 참고하세요.
