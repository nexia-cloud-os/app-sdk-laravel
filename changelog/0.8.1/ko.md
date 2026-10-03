---
status: published
version: 0.8.1
date: 2026-10-03
title: 폐기된 PHP 심볼 검사 보정
description: PHP 테스트의 프런트엔드 타입 문자열 오탐을 수정합니다.
---

# Laravel SDK 0.8.1

폐기된 PHP 타입의 짧은 이름은 문자열·주석·인라인 HTML을 제외한 PHP 코드에서 검사합니다. 전체 네임스페이스가 있는 폐기 심볼은 파일 전체에서 계속 검사합니다. 정상적인 프런트엔드 `ResourceActionDescriptor` 검증 문자열이 폐기된 PHP API 사용으로 보고되던 문제를 수정합니다. Core 0.7.0·React SDK 0.8.0과 호환되며 앱 변경은 필요하지 않습니다.
