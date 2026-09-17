---
status: published
version: 0.6.0
date: 2026-09-16
title: 현대식 Composition과 개발 계약
description: 현재 Composition 계약과 업무 알림 및 자동승인 계약을 릴리스합니다.
---

## 변경

- SDK 배포 경로를 비공개 Composer 소스용 GitHub(`nexia-cloud-os/app-sdk-laravel`)와 `@amuzcorp/nexia-app-sdk-react`용 공개 npmjs로 옮깁니다. 기존 `0.5.0` artifact의 버전과 내용은 유지합니다. 사용 환경의 registry·저장소 설정을 갱신하세요. npm 다운로드에는 토큰이 필요 없고 비공개 Composer 소스에는 GitHub 접근 권한이 필요합니다.

- Resource Composition은 이제 단일 현대식 `schema_version: 1` wire 계약을 사용합니다. graph `relationships`, `population`, typed `where` predicate와 현대식 result 필드를 필수로 하며, 행 결과에는 `row_source`도 필요합니다.
- React SDK의 `packages/react`에서 `pnpm run build:watch`로 소스 변경을 감시할 수 있습니다. 변경된 소스를 공개 `dist` export에 반영하고, 타입 오류를 표시하며 수정 후 다시 빌드합니다. Core는 로컬 SDK 소스를 선택했을 때 이 감시를 Vite와 함께 실행할 수 있습니다. 배포 패키지명과 공개 export는 유지합니다.
- `ResourceReferenceResolutionContext`에 범위가 제한된 소유자 정의 선택기 필터와 정렬을 전달할 수 있습니다. 리소스 제공자는 이를 페이지네이션 전에 처리할 수 있으며, 새 인자는 선택 사항이므로 기존 생성 코드는 호환됩니다.

## 제거

- schema-v2부터 schema-v5 parser와 이전 schema-v1의 relationship·`filters` 형태를 제거했습니다. 기존 저장 composition은 조정된 환경 초기화 후 현대식 schema-1 형태로 새로 만들어야 하며 SDK는 이전 payload를 변환하지 않습니다.

## 추가

- 영속 이벤트에서 업무 결과 알림을 기여·발행하는 App 공통 계약을 추가했습니다. 구현하는 Core가 테넌트 범위, 수신자 접근 권한, 알림 설정, 중복 방지와 표시를 담당합니다.
- 자동승인의 평가 정보와 결과 계약을 추가했습니다. App은 현재 업무 리소스에 연결된 정보를 제공하며, Core가 정책 권한을 통제하고 사람 서명을 합성하지 않고 결정 근거를 보존합니다.

## 업그레이드 필요

Laravel·React 0.6.0 artifact는 Core 0.6.0과 함께 사용하세요. 이전 Composition payload는 자동 변환되지 않습니다. 영향을 받는 저장된 Composition을 조정된 업그레이드 절차에 따라 다시 생성하고, 파괴적인 초기화를 자동 실행하지 마세요. 채택 전에 소비 패키지의 호환성 범위를 검토하고 갱신하세요.
