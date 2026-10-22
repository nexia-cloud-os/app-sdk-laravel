---
status: published
version: 0.10.5
date: 2026-10-22
title: 외부 API 연결 선언
description: App 소유 외부 API 작업을 선언하고 관리자 검토를 받습니다.
---

App은 `ExternalApiDefinition`으로 외부 HTTP API 작업과 웹훅 수신 설정을 선언할 수 있습니다. 선택적 `ExternalIntegrations::submitDeclared` 메서드는 선언 키로 작업을 제출합니다. 선언 자체에는 인증 정보가 없으며 접근 권한을 부여하지도 않습니다.

이 계약을 사용하기 전에 대응하는 Core와 격리 런타임 릴리스를 적용하고 App을 SDK 0.10.5로 다시 빌드해야 합니다. App이 연결을 사용하려면 관리자가 선언을 검토하고 적용해야 합니다.
