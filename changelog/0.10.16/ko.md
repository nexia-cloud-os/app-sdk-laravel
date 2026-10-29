---
status: published
version: 0.10.16
date: 2026-10-29
title: 중단 후 재개할 수 있는 가져오기 계약
description: 가져오기 체크포인트와 권한을 확인하는 결과 페이지를 선언합니다.
---

PHP SDK 0.10.16은 선택 계약 `IncrementalResourceImportPipeline`, `SeekableResourceImportRowSource`, `ImportPipelineStep`을 추가합니다. 기존 동기 파이프라인도 지원합니다. `validateNext()`와 `applyNext()`에서 제한된 작업 단위와 진행 위치를 한 트랜잭션으로 저장하세요. `slice(offset, limit)`의 위치는 0부터 시작하는 데이터 행 기준이며 원래 행 키를 유지합니다. 진행 중 체크포인트는 검증 완료나 적용 가능한 배치가 아닙니다. 호출마다 사용자·조직 권한을 다시 확인하고 전체 파일의 중복을 검증하며, 이미 저장한 쓰기를 반복하지 않아야 합니다.

파이프라인의 `resultRowsPath`로 권한을 확인하는 페이지별 결과 조회를 제공할 수 있습니다. `skipped`는 저장 없이 제외한 행이고 `not_processed`는 중단으로 저장되지 않은 행입니다. 둘 다 `unchanged`와 구분하며 생성·수정 건수에는 실제 저장된 행만 포함합니다. 업로드 한도는 바뀌지 않습니다.

사용 전 대응하는 Core 가져오기 처리·마이그레이션과 Manager 런타임을 함께 적용해야 합니다. SDK 설치만으로 업무 흐름이 활성화되지는 않습니다. [가져오기·내보내기 가이드](https://developers.nexia.to/docs/building-apps-import-and-export)와 [발행된 계약 소스](https://github.com/nexia-cloud-os/app-sdk-laravel/tree/v0.10.16/src/ResourceImport)를 확인하세요.
