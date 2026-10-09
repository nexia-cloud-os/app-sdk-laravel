---
status: published
version: 0.7.1
date: 2026-10-01
title: Composer와 sandbox 공용 SDK
description: Composer 호스트와 격리 런타임에서 사용하는 호환 앱 계약입니다.
---

- 격리 런타임에서 `StoredFile.disk`와 `path`는 빈 값입니다. 파일 내용은 `AuthorizedFileReader`, 런타임 내 글꼴은 `PdfFontProvider`로 읽으세요. Core 저장 경로는 앱 파일 시스템 API가 아닙니다.
- 문서 생성 용도와 기존 권한 키를 `OfficialSealUseDescriptor`로 선언하고 `AppDescriptorContribution`으로 제공하세요. 선언만으로 접근 권한이 부여되지 않습니다.
- `TenantAppInitializerContribution`, `VersionedTenantAppInitializerContribution`, `TenantAppInitializationContext`로 설치 필수 기본값을 제공할 수 있습니다. 초기화는 멱등해야 하며 외부 부작용이 없어야 합니다. Core 모델을 가져오지 말고 SDK 인터페이스를 사용하세요.
- `ResourceDescriptor::publicForIntegration`은 Builder 표시와 별도로 개발자 명세 공개를 제어합니다. `integrationContract()`는 내부·제거된 Resource를 제외하며 데이터 접근 권한을 부여하지 않습니다.
- 생성된 라우트는 `Nexia\Http\Middleware::APP_REQUEST`를 사용합니다. Core 테넌트 미들웨어를 직접 가져오지 마세요. 선언된 기능을 구현하는 Core·런타임 릴리스가 필요합니다.
- 앱은 의사결정 결과 템플릿, Runtime 요구사항, 설정 정의와 실행 미지원 Action을 선언할 수 있습니다. 필수 기능이 지원되지 않으면 거부하며 선언만으로 설정 접근·비동기 작업·변경이 활성화되지 않습니다.
- 이 릴리스의 PHP 패키지 이름은 `nexia/sdk-laravel`이며 같은 버전의 `amuzcorp/nexia-app-sdk-laravel`을 대체합니다. 루트 의존성과 잠금 파일을 갱신하세요. 앱 호환 범위는 넓히지 않으며 React 버전은 독립적입니다.
- 앱은 지원 의존성 정책을 따라야 하며 실행형 autoload 훅이나 다른 앱의 직접 의존성을 추가할 수 없습니다. `platform`이 없는 기존 메타데이터는 읽을 수 있지만 새 기능에는 대응하는 호스트가 필요합니다.
