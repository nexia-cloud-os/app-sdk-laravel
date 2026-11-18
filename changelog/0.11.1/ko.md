---
status: published
version: 0.11.1
date: 2026-11-18
title: 설치 앱 작업 동시 실행
description: 독립 작업과 읽기 전용 이관 계획 조회를 선택적으로 선언할 수 있습니다.
---

가져오기 파이프라인에서 최초 계획 조회와 검토 페이지가 쓰기나 행 잠금 없이 동작하는지 확인한 뒤 `supportsReadOnlyPlan`을 선언할 수 있습니다. 선언을 사용하려면 호환 SDK·Core·앱 런타임이 필요합니다. 기존 선언도 계속 동작합니다. [가져오기 및 내보내기](https://developers.nexia.to/ko/docs/building-apps-import-and-export).

앱 작업과 가져오기 파이프라인은 순차 실행할 작업에 같은 `workConflictGroup`을 선언할 수 있습니다. 서로 다른 그룹을 지정하기 전에 공통 데이터 변경과 외부 서비스 호출의 영향을 확인하세요. 미선언 작업은 기존 방식으로 배정합니다. 호환 SDK·Core·앱 런타임이 필요합니다. [가져오기 및 내보내기](https://developers.nexia.to/ko/docs/building-apps-import-and-export).
