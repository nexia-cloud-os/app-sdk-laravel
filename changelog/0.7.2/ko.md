---
status: published
version: 0.7.2
date: 2026-10-01
title: 공개 PHP 패키지 배포
description: Packagist에서 공식 패키지 이름으로 PHP SDK를 설치합니다.
---

- PHP 네임스페이스를 유지하며 `nexia-cloud-os/sdk-laravel`을 배포합니다. 이전 SDK 패키지 이름은 `self.version`으로만 대체하므로 호환되는 기존 앱의 버전 조건을 유지합니다.
- 격리 의존성 검증은 공식 SDK가 선언한 정확한 이전 이름 대체만 허용하며, 호환되지 않는 버전과 다른 패키지의 대체 선언은 계속 거부합니다.
- 게시 후 루트 Composer 의존성과 lock을 갱신해야 합니다. 이름 변경만으로 0.6 전용 앱이 0.7과 호환되지는 않습니다. React npm 패키지는 별도로 버전을 관리합니다.
- 원본 저장소의 릴리스 PR을 main에 병합하면 패키지를 검증하고 배포 저장소에 해당 패키지 파일과 태그를 발행합니다. 기존 태그는 이동하지 않습니다.
