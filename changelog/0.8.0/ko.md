---
status: published
version: 0.8.0
date: 2026-10-03
title: 공통 네이티브 앱 계약
description: 네이티브 v2 앱, 격리 데이터 계약과 호스트 관리 액션.
---

# Laravel SDK 0.8.0

## 필수 전환
- 대응 Core 0.7.0과 런타임 구현이 필요합니다. 호환성이 변경되는 0.x 릴리스입니다. 앱은 `runtime: laravel`인 `nexia.json` v2와 정식 `nexia-cloud-os/sdk-laravel` 패키지를 사용합니다. 이전 Composer 패키지 이름의 대체 선언과 `extra.nexia` 매니페스트는 제거됩니다.
- 제거된 PHP `ResourceActionDescriptor` 대신 `ActionDefinition`을 사용합니다. Party·Organization의 이전 잠금 메서드는 명시적인 쓰기 사전 조건 또는 참조 선택 계약으로 전환합니다. 이 계약은 Core와 앱의 트랜잭션을 하나로 합치지 않습니다.

## 계약
- 앱 범위 DB 연결, 외부 쓰기 계정 발급, 비동기 작업·예약 카탈로그, 버전이 있는 동기 액션, 범위별 Backend 설정을 제공합니다.
- 검증된 참조 스냅샷, 가져오기 파일 기능, 전송 스키마·모델 선언을 런타임 경계에서 유지합니다. 하이픈이 있는 앱 위젯 식별자와 서명된 결재·대상 계약도 공통 카탈로그로 전달합니다.

PHP 산출물은 React 0.8.0과 별도로 게시한 뒤 검증된 산출물과 다시 빌드한 앱을 Core의 잠금 파일에 반영합니다. 소스 체크아웃이나 로컬 별칭은 게시된 릴리스가 아닙니다.
