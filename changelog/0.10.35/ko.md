---
status: published
version: 0.10.35
date: 2026-11-16
title: 가져오기 작업 체크포인트
description: 증분 가져오기 작업을 위한 선택적 체크포인트 계약을 추가합니다.
---

증분 가져오기 파이프라인은 `WorkCheckpointedResourceImportPipeline`을 구현하여 각 적용 단계를 호스트가 제공하는 작업 식별자에 연결할 수 있습니다. 체크포인트와 앱 데이터를 함께 커밋하고 원래 소유자와 배치를 검증하세요. 결과가 불확실한 외부 쓰기는 계속 검토해야 합니다. 복구 기능을 켜기 전에 호환 Core와 Runtime을 적용하세요.
