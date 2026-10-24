---
status: published
version: 0.10.10
date: 2026-10-24
title: 공통 문서 데이터와 프로필 표시 조건
description: 앱 공통 문서 데이터 계약과 조건부 프로필 기여를 지원합니다.
---

- `DocumentDataHost`와 제공자 계약으로 전자서명 외에서도 문서 데이터 카탈로그와 해석 기능을 사용합니다. 기존 전자서명 계약은 호환됩니다.
- `profile.team.overview`와 `availabilityResourceKey`로 사용자별 프로필 표시 조건을 선언합니다. 권한 검증은 소유 앱이 유지합니다.
- 대응 Core 0.8.152 호스트와 Sandbox Manager 0.1.0-alpha.75 런타임을 함께 채택해야 합니다. 패키지 게시와 호스트 의존성 반영은 별도 단계입니다.
