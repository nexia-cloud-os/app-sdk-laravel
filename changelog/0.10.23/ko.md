---
status: published
version: 0.10.23
date: 2026-11-06
title: Composition 쿼리의 조직 참조
description: 권한이 적용된 쿼리에 조직 공개 ID와 이름을 표시합니다.
---

Composition provider에서 Laravel SDK로 법인과 조직 단위의 공개 ID와 이름을 표시할 수 있습니다. 사용자와 조직 범위에 대한 권한 검사는 provider에서 계속 적용해야 합니다. [리포트 쿼리 안내](https://developers.nexia.to/ko/docs/app-sdk-governed-reports)와 [SDK 구현](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.23/src/Laravel/ResourceComposition/OrganizationReferenceProjection.php)을 참고하세요.
