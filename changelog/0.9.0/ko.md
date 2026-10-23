---
status: published
version: 0.9.0
date: 2026-10-12
title: 실행할 업무 설계 문서의 명확한 지정
description: 설계 문서와 실행할 프로세스를 구분하는 업무 시작 계약입니다.
---

## 적용 시 필요한 변경

- `ProcessStartBindingDescriptor`는 `definitionKey` 대신 `documentKey`를 요구합니다. 생성자의 명명 인자와 저장된 카탈로그를 변경하세요. 기존 필드는 거부되며, `processId`와 함께 실행할 프로세스를 지정합니다.
- 호스트 테스트 데이터는 `createBpmnDocument()`로 문서를 만든 뒤 `createProcessDefinition()`에 `bpmn_document_id`와 `bpmn_process_id`를 지정합니다.
- 대응하는 Core 0.8.0 구현과 사용 App을 함께 적용해야 합니다. 활성화 전에 격리 App 산출물을 다시 빌드하고 카탈로그를 재설치하세요.

## 사용 방법

- [업무 프로세스 계약](https://developers.nexia.to/ko/docs/app-sdk-business-process-contracts)
