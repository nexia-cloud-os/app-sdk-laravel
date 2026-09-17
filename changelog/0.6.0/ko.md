---
status: published
version: 0.6.0
date: 2026-09-17
title: 현대식 Resource Composition 및 워크플로 계약
description: 현재 Resource Composition wire 계약과 App 공통 알림 및 자동승인 계약을 도입합니다.
---

## 변경

- Resource Composition 숫자 measure은 소유자가 발행한 JSON `null` unit을 그대로 사용할 수 있습니다. count와 distinct-count measure은 계속 `count` unit이 필요합니다.
- Resource Composition은 이제 단일 현대식 `schema_version: 1` wire 계약을 사용합니다. graph `relationships`, `population`, typed `where` predicate와 현대식 result 필드를 필수로 하며, 행 결과에는 `row_source`도 필요합니다.
- `ResourceReferenceResolutionContext`에 범위가 제한된 소유자 정의 선택기 필터와 정렬을 전달할 수 있습니다. 리소스 제공자는 이를 페이지네이션 전에 처리할 수 있으며, 새 인자는 선택 사항이므로 기존 생성 코드는 호환됩니다.

## 제거

- schema-v2부터 schema-v5 parser와 이전 schema-v1의 relationship·`filters` 형태를 제거했습니다. 기존 저장 composition은 조정된 환경 초기화 후 현대식 schema-1 형태로 새로 만들어야 하며 SDK는 이전 payload를 변환하지 않습니다.

## 추가

- 영속 이벤트에서 업무 결과 알림을 기여·발행하는 App 공통 계약을 추가했습니다. 구현하는 Core가 테넌트 범위, 수신자 접근 권한, 알림 설정, 중복 방지와 표시를 담당합니다.
- 자동승인의 평가 정보와 결과 계약을 추가했습니다. App은 현재 업무 리소스에 연결된 정보를 제공하며, Core가 정책 권한을 통제하고 사람 서명을 합성하지 않고 결정 근거를 보존합니다.

## 업그레이드 필요

- 저장된 모든 Resource Composition payload를 읽기 전에 현재 `schema_version: 1` 형태로 바꾸세요. 이전 schema-v1부터 schema-v5 payload는 거부되며 SDK에 전송하면 안 됩니다.
- Core lock을 바꾸기 전에 Laravel SDK 0.6.0 artifact를 깨끗한 Composer consumer에서 릴리스·적용하세요. 로컬 소스 checkout과 path repository는 릴리스 근거가 아닙니다.
