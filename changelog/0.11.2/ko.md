---
status: published
version: 0.11.2
date: 2026-11-19
title: 테넌트별 앱 예약 작업
description: 앱에서 테넌트별로 개별 예약 작업을 켜고 끌 수 있습니다.
---

앱에서 테넌트별로 개별 예약 작업을 켜고 끌 수 있습니다. 호환되는 PHP SDK와 Core 스케줄러를 함께 적용한 뒤 사용합니다. [예약 작업 안내](https://developers.nexia.to/docs/building-apps-background-work) · [PHP SDK 소스](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/main/src/AsyncWork/AppWorkSchedule.php)

이 선언을 사용하기 전에 Core와 앱 런타임에 호환 SDK를 적용하고 Core 테넌트 마이그레이션을 완료하세요.
