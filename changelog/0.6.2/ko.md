---
status: published
version: 0.6.2
date: 2026-09-17
title: 단위 없는 숫자 Composition measure
description: 소유자가 단위를 발행하지 않은 숫자 measure를 허용합니다.
---

## 수정

- Resource Composition이 비집계 숫자 measure의 `null` unit을 허용합니다. count와 distinct-count measure은 계속 `count` unit이 필요합니다.

## 호환성

- 기존 payload는 계속 유효합니다. 마이그레이션이 필요하지 않습니다.
