---
status: published
version: 0.11.5
date: 2026-11-22
title: 대상자 범위 조회와 저장 참조
description: 대상자 범위 조회와 저장된 Resource 참조 대상을 선언합니다.
---

앱은 `SubjectScopedResourceQuery`를 구현해 허용된 대상자와 조직 범위에 맞게 조회를 제한할 수 있습니다. 선언만으로 접근 권한이 생기지 않으며 호환되는 Core와 앱 런타임이 필요합니다.

문자열 ID 필드에는 `reference_resource_key`를 선언할 수 있습니다. 여러 대상 중 하나를 행의 키로 구분한다면 `accepted_resource_keys`와 `target_resource_key_field`를 사용합니다. 이 선언은 선택기나 읽기 권한을 추가하지 않습니다. 참조 탐색에 사용하기 전에 호환되는 Core를 적용하세요. [Resource Reference 안내](https://developers.nexia.to/ko/docs/platform-extensions-resource-references)와 [SDK 계약](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.5/src/AppDescriptors/ResourceDescriptorContract.php)을 참고하세요.
