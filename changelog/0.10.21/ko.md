---
status: published
version: 0.10.21
date: 2026-11-05
title: 사용자 작업 컨텍스트
description: 앱 제출 처리에서 호스트가 제공한 작업 컨텍스트를 사용합니다.
---

`ProcessUserTaskSubmissionInvocation`에 선택적 `taskContext`가 추가됩니다. 폼 입력과 분리해 전달되며 기존 호출자의 기본값은 빈 배열입니다. 앱은 항목과 소유 관계, 선행 조건, 사용자의 변경 권한을 검증해야 합니다. 이 컨텍스트를 필수로 사용하기 전에 대응 Core와 앱 런타임을 적용하세요. [업무 프로세스 계약](https://developers.nexia.to/ko/docs/app-sdk-business-process-contracts)과 [게시된 SDK 계약](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.21/src/Process/ProcessUserTaskSubmissionInvocation.php)을 참고하세요.
