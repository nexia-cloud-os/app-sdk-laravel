---
status: published
version: 0.5.0
date: 2026-09-15
title: 독립 Laravel·React SDK 패키지
description: 통합 SDK를 별도 배포 Laravel·React 패키지, canonical 계약로 교체합니다.
---

## 변경

- 통합 App SDK를 별도 배포하는 Laravel 패키지 `amuzcorp/nexia-app-sdk-laravel`과 React 패키지 `@amuzcorp/nexia-app-sdk-react`로 교체했습니다.
- PHP 계약은 canonical namespace를 사용합니다. 이전 통합 패키지 identity와 legacy namespace bridge는 제공하지 않습니다.

## 추가

- `NxButton`, `NxIconButton`에 대응하는 화면 이동 컴포넌트 `NxLinkButton`, `NxIconLink`를 추가했습니다. canonical `href`를 받아 Core host가 화면 이동을 명령 callback과 구분하고 Work Tab 탐색을 제공할 수 있습니다.
- `NxStatCard`와 `NxOverviewBand`는 canonical `href`로 이동합니다.

## 업그레이드 필요

- 폐기된 통합 SDK 의존성과 import를 `amuzcorp/nexia-app-sdk-laravel`, `@amuzcorp/nexia-app-sdk-react`의 `^0.5.0`으로 교체하세요.
- PHP import를 canonical contract namespace로 바꾸세요. legacy 패키지 의존성, alias, namespace wrapper를 유지하지 마세요.

## 사용 방법

- [App 화면 만들기](https://developers.nexia.to/ko/docs/building-apps-build-a-frontend-surface)
- [SDK 사용 시작하기](https://developers.nexia.to/ko/docs/app-sdk-overview)
