---
status: published
version: 0.8.2
date: 2026-10-03
title: 호스트가 제공하는 앱 DB 연결
description: 앱 DB 어댑터가 호스트의 명시적 연결 설정으로 계약을 해석합니다.
---

# Laravel SDK 0.8.2

앱 DB·Schema 파사드와 모델 연결 선택이 기존 권한 어댑터와 같은 호스트 주입 방식을 사용합니다. SDK에서 Laravel 전역 런타임 함수를 호출하지 않습니다.

Core와 Sandbox Manager는 서비스 등록 시 연결 해석기를 설정해야 합니다. 설정되지 않은 접근은 거부하며 호출할 때마다 현재 호스트 서비스를 사용해 테넌트·앱 권한 경계를 유지합니다. 앱 개발자가 사용하는 API는 바뀌지 않습니다.
