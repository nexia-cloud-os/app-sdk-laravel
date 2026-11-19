---
status: published
version: 0.11.3
date: 2026-11-19
title: 디렉터리와 결재 조회
description: 디렉터리는 Eloquent로 조회하고 결재 목록과 상세는 권한이 적용된 쿼리로 읽습니다.
---

Party, User, 법인, 운영조직, 사업장은 PHP SDK의 읽기 전용 모델을 사용합니다. 호환되는 관리형 App DB에서 Eloquent 조건과 정렬을 추가하거나 App 테이블과 조인할 수 있습니다. 개발자 로컬 DB에서는 호환 CLI가 준비한 스냅샷이 필요합니다. [디렉터리 모델 가이드](https://developers.nexia.to/ko/docs/building-apps-core-database-references) · [PHP SDK 디렉터리 모델](https://github.com/nexia-cloud-os/app-sdk-laravel/tree/v0.11.3/src/Laravel/Directory)

결재 목록은 `Approval::query($legalEntity)->paginate()`, 상세는 `->find($publicId)`를 사용합니다. 원본 App이 공개한 필터는 리소스 키를 지정해 `throughResource()`로 적용합니다. 결재 결과 정산에는 `ApprovalHost::caseSummary()`를 사용합니다. [전자 결재 계약](https://developers.nexia.to/ko/docs/app-sdk-approval-contracts) · [PHP SDK Approval 소스](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.11.3/src/Approval/Approval.php)

`IdentityQueries`는 deprecated입니다. 일괄 조회 결과를 포함해 기존 호출은 계속 동작합니다. 새 Party·User 조회는 디렉터리 모델을, 개인 프로필 조회는 `PersonProfileDirectory`를 사용하세요. 새 호출을 사용하기 전에 호환되는 Core와 App 런타임을 적용하세요.
