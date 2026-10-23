---
status: published
version: 0.8.3
date: 2026-10-05
title: 예약 작업 카탈로그 저장 호환성
description: JSON 객체의 필드 순서와 무관하게 예약 작업을 검증합니다.
---

# Laravel SDK 0.8.3

## 예약 작업 카탈로그 저장 호환성

- 예약 작업의 JSON 필드 순서가 바뀌어도 허용하고, 필수 필드 누락과 알 수 없는 필드는 계속 거부합니다. 정상 예약 작업이 PostgreSQL JSON 저장 후 실패하는 문제를 해결합니다. Core 카탈로그 정규화 수정과 함께 적용하고 해당 앱 실행 산출물을 다시 빌드해야 합니다.

## 사용 방법

- [백그라운드·예약 작업 실행하기](https://developers.nexia.to/ko/docs/building-apps-background-work)
- [SDK 사용 시작하기](https://developers.nexia.to/ko/docs/app-sdk-overview)
