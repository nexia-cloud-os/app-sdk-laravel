---
status: published
version: 0.11.9
date: 2026-11-26
title: App 작업 실패 보고
description: 앱에서 처리한 쓰기 실패를 호환되는 Core에 보고합니다.
---

- `Nexia\AppRuntime\OperationFailure::with()`로 앱에서 처리한 쓰기 실패 사유를 기존 JSON 응답에 담을 수 있습니다. 호환되는 Core에서 작업, 실패 단계와 코드, 보고된 내부 HTTP 상태와 건수를 확인할 수 있습니다. [App 테스트하기](https://developers.nexia.to/ko/docs/building-apps-test-an-app)
