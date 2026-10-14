---
status: published
version: 0.9.2
date: 2026-10-14
title: 관리되는 외부 작업 호출
description: 호스트를 통해 외부 HTTP·MCP 작업을 제출합니다.
---

## 추가

- `ExternalIntegrations`로 연결 인증 정보를 App에 전달하지 않고 등록된 HTTP·MCP 작업을 제출하고 권한 범위 내 결과를 조회할 수 있습니다.

## 적용 안내

- 대응 Core와 격리 런타임이 필요합니다. App 트랜잭션 커밋 후 호출하고 비동기 App 작업에 `integration.submit`과 `integration.result`를 선언하세요.
- 동일 요청에는 같은 멱등 키를 사용하세요. 결과가 `unknown`이면 새 요청을 시작하기 전에 외부 처리 상태를 확인하세요.
