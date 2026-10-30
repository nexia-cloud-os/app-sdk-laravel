---
status: published
version: 0.10.17
date: 2026-10-30
title: 업무 결재 담당자 권한
description: 업무 양식의 결재 담당자에게 필요한 추가 권한을 선언합니다.
---

PHP SDK 0.10.17은 법인 범위의 결재·협조 담당자 권한을 선언하는 선택 항목 `ApprovalFormBindingDescriptor::requiredActorPermissionKeys`를 추가합니다. 기존 바인딩의 동작은 유지됩니다. 이 항목을 선언하기 전에 대응 Core 호스트와 네이티브 런타임을 함께 적용하세요. [결재 계약 안내](https://developers.nexia.to/docs/app-sdk-approval-contracts#restrict-business-form-approvers)와 [게시된 선언](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.17/src/AppDescriptors/ApprovalFormBindingDescriptor.php)을 참고하세요.
