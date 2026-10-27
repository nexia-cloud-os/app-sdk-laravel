---
status: published
version: 0.10.15
date: 2026-10-28
title: 결재 접수 영수증과 권한 범위를 지정한 프로필 이력
description: 결재 작업을 영속적으로 접수하고 소유 리소스를 기준으로 프로필 이력 권한을 확인합니다.
---

PHP SDK 0.10.15에 선택적으로 사용하는 `ApprovalSubmissionHost` 계약이 추가되었습니다. 호스트가 결재 요청을 나중에 처리하도록 접수하면 `ApprovalSubmissionReceipt`를 반환합니다. 이 영수증은 결재 건이나 제출 완료를 뜻하지 않습니다. App에서 선언한 후처리 작업은 `commit` 또는 `release`를 중복 실행해도 안전하게 처리해야 합니다. 기존 동기 `ApprovalHost` 호출은 계속 사용할 수 있습니다. 비동기 접수를 활성화하기 전에 대응하는 Core와 샌드박스 런타임을 적용하세요. [결재 계약 안내](https://developers.nexia.to/ko/docs/app-sdk-approval-contracts)를 참고하세요.

`PersonProfileHistory::pageForAuthorizedHr()`에 레코드별 권한 확인을 위한 선택적 소유자 `ResourceRef` 인자가 추가되었습니다. 이 인터페이스를 직접 구현한 App은 선택적 인자를 추가해야 합니다. 인자를 전달하지 않는 호출에는 기존 디렉터리 권한이 필요합니다. [SDK 계약 안내](https://developers.nexia.to/ko/docs/app-sdk-sdk-contracts)를 참고하세요.
