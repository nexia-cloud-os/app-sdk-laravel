---
status: published
version: 0.5.0
date: 2026-09-15
title: 독립 Laravel·React SDK 패키지
description: 통합 SDK를 별도 배포 Laravel·React 패키지, canonical 계약, 추가 전용 wire 카탈로그로 교체합니다.
---

## 변경

- 통합 App SDK를 별도 배포하는 Laravel 패키지 `amuzcorp/nexia-app-sdk-laravel`과 React 패키지 `@amuzcorp/nexia-app-sdk-react`로 교체했습니다.
- PHP 계약은 canonical namespace를 사용합니다. 이전 통합 패키지 identity와 legacy namespace bridge는 제공하지 않습니다.
- 기존 PHP·React vocabulary에 private 버전 wire 카탈로그와 패키지별 fixture를 추가했습니다. wire catalog v1은 추가만 허용하며, 제거·이름 변경·의미 변경에는 새 카탈로그 버전과 소비자 전환이 필요합니다.

## 추가

- `NxButton`, `NxIconButton`에 대응하는 화면 이동 컴포넌트 `NxLinkButton`, `NxIconLink`를 추가했습니다. canonical `href`를 받아 Core host가 화면 이동을 명령 callback과 구분하고 Work Tab 탐색을 제공할 수 있습니다.
- `NxStatCard`와 `NxOverviewBand`는 canonical `href`로 이동합니다.

## 업그레이드 필요

- 폐기된 통합 SDK 의존성과 import를 `amuzcorp/nexia-app-sdk-laravel`, `@amuzcorp/nexia-app-sdk-react`의 `^0.5.0`으로 교체하세요.
- PHP import를 canonical contract namespace로 바꾸세요. legacy 패키지 의존성, alias, namespace wrapper를 유지하지 마세요.
- Core lock을 바꾸기 전에 공개된 두 artifact를 깨끗한 Composer·npm consumer에서 함께 적용하세요. local path repository, workspace override, archive는 릴리스 근거가 아닙니다.
