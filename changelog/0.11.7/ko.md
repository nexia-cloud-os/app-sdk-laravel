---
status: published
version: 0.11.7
date: 2026-11-24
title: 현재 게시된 양식으로 전자결재 상신
description: 현재 게시된 결재 경로와 양식으로 초안을 상신할 수 있습니다.
---

지원되는 결재 작성 요청에서 선택 계약인 `CurrentApprovalSubmissionHost`를 사용할 수 있습니다. `availableFor()`로 지원 여부를 확인한 뒤 초안과 접수번호를 함께 저장합니다. 등록한 후처리는 확정 시 `ApprovalSubmissionDelivery::currentResult`에서 결재 건, 경로, 양식, 연결 규격, 결재선을 받습니다. 해당 기능이 없는 호스트에서는 기존 동기 흐름을 유지하며, 적용 시 대응 Core와 App Runtime 릴리스가 필요합니다. [전자결재 계약](https://developers.nexia.to/ko/docs/app-sdk-approval-contracts)을 참고하세요.

[SDK 소스](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.7/src/Approval/Contracts/CurrentApprovalSubmissionHost.php)
