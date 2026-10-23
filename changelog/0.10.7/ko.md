---
status: published
version: 0.10.7
date: 2026-10-22
title: 공개 참조 조회 진단
description: 카탈로그 검증에서 잘못된 공개 참조 조회 필드를 확인합니다.
---

카탈로그 검증이 공개 descriptor 필드 누락, 중복 조회 키, 잘못된 페이징·해결 선언을 `CatalogValidationException::$issues`로 제공합니다. Core는 같은 데이터 검사를 `ResourceQueryDiagnostics::issues()`로 사용할 수 있습니다. 기존 공개 참조 계약을 검사하며 일반 모델 목록 필드는 별도입니다.

대응하는 진단 소비자와 함께 Core와 App 런타임에 PHP SDK 0.10.7을 반영한 뒤 런타임 이미지를 다시 빌드하세요. Devtools 0.2.13은 소스 검사만으로 동적 카탈로그를 평가하지 않는다는 점을 안내합니다. React SDK 갱신은 필요하지 않습니다.

## 사용 방법

- [다른 앱의 레코드 참조하기](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)
