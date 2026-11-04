---
status: published
version: 0.10.20
date: 2026-11-04
title: 업무 API 권한 선언
description: 업무 API 핸들러가 추가로 검사하는 권한을 선언합니다.
---

App 핸들러는 `BusinessApiPermissions`로 항상 필요한 추가 권한을 선언할 수 있습니다. 역할 미리보기에 사용하기 전에 대응하는 Core 수신부와 Manager 런타임을 적용하세요. 조건부 검사와 실제 권한 검사는 App이 담당합니다. [개발자 API 안내](https://developers.nexia.to/ko/docs/building-apps-add-api-and-authorization)와 [SDK 계약](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.20/src/AppRuntime/BusinessApiPermissions.php)을 참고하세요.
