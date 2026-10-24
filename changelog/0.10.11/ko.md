---
status: published
version: 0.10.11
date: 2026-10-24
title: 버전별 Laravel 시더
description: 일반 Laravel 시더로 앱 필수 데이터를 등록합니다.
---

`VersionedTenantAppSeederContribution`으로 순서가 있는 시더 버전을 등록합니다. Core 0.8.153과 Manager alpha.76은 실행·게시 이력 검증을 제공하고 CLI alpha.42는 재시도 결과를 안내합니다. 기존 초기화기는 계속 지원하며 기존 데이터베이스를 자동 변환하지 않습니다.

[등록·복구 안내](https://developers.nexia.to/ko/docs/platform-extensions-required-installation-content)와 [SDK 계약](https://github.com/nexia-cloud-os/app-sdk/blob/laravel-v0.10.11/packages/laravel/src/AppRuntime/Contracts/VersionedTenantAppSeederContribution.php)을 참고하세요.
