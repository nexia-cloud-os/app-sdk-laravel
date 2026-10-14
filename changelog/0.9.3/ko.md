---
status: published
version: 0.9.3
date: 2026-10-14
title: 개인 및 법인 등록 정보
description: 권한이 확인된 호스트 계약으로 선택적 생년월일과 법인 등록 정보를 조회합니다.
---

앱에서 `PersonProfileView`의 선택적 ISO 생년월일과 `HasLegalEntityProfile::legalProfile()`의 법인 등록 정보를 조회할 수 있습니다. 법인 정보는 등록명, 사업자등록번호, 대표자와 등록 주소를 포함합니다. 기존 생성자 호출은 호환되며 없는 값은 null을 유지합니다.

새 필드를 사용하기 전에 대응하는 Core 구현, nullable 테넌트 마이그레이션과 격리 런타임 어댑터를 함께 적용해야 합니다. 기존 발급 문서는 다시 작성하지 않습니다.
