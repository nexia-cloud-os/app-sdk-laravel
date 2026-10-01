---
status: published
version: 0.7.1
date: 2026-10-01
title: Composer와 sandbox 공용 SDK
description: Composer 호스트와 격리 런타임에서 사용하는 호환 앱 계약입니다.
---

- 격리 런타임의 `StoredFile.disk`·`path`는 빈 문자열임을 명시합니다. 권한 있는 원본 읽기는 `AuthorizedFileReader`, 실행 중인 런타임의 로컬 글꼴은 `PdfFontProvider`를 사용하세요. Core 저장 경로를 앱 파일 시스템 API로 사용하지 않습니다.
- 앱의 정확한 문서 생성 용도와 기존 권한 키를 선언하는 `OfficialSealUseDescriptor`를 추가합니다. `AppDescriptorContribution`으로 제공하면 대응 Core가 현재 권한을 검사하고 호출 사용자에 귀속된 직인 사용을 기록합니다. 선언 자체는 권한을 부여하지 않습니다. 사용 전 SDK·Core 콜백·격리 런타임을 함께 적용하세요.
`ResourceDescriptor::integrationContract()`가 설치 앱과 격리 앱의 공통 연동 명세를 제공합니다. 내부 전용·제거된 리소스는 `null`을 반환하고 공개 조합용 이벤트만 포함합니다. 구현 경로를 노출하거나 조회·변경 권한을 부여하지 않습니다. 선택한 개발 앱의 명세를 조회하려면 대응하는 Core·샌드박스 런타임 변경을 함께 적용하세요.

## 추가

- 공개 `TenantAppInitializerContribution`, `VersionedTenantAppInitializerContribution`, 불변 `TenantAppInitializationContext`로 Core import 없이 App 필수 데이터를 초기화합니다. 대응 Core 설치기가 contribution을 발견하고 App 소유권과 완료 상태를 검사합니다. 컨텍스트는 Core 모델 대신 definition·tenantId·initializationVersion을 제공합니다. 초기화는 멱등해야 하며 외부 부작용을 넣지 않습니다.
- 선택적 `ResourceDescriptor::publicForIntegration`으로 Builder 공개 여부와 별개로 개발자용 연동 계약을 공개합니다. 생략하면 기존 공개 범위를 유지합니다. 명세 공개는 실행 권한을 부여하지 않습니다.
- DB 검색만 제공하는 Laravel 호스트는 `ScoutSearchResolverRegistry::configure`에 identity resolver를 `null`로 전달하고 실제 Scout engine resolver를 연결할 수 있습니다. 기존 호스트의 identity resolver는 그대로 유지합니다. 공유 테넌트 검색을 활성화하는 기능은 아닙니다.

## 적용

대응 Core 초기화·계약 조회 변경과 tenant migration `2026_09_23_060000_track_app_initialization_application`을 함께 적용합니다. 기존 App manifest와 ResourceDescriptor 호출은 그대로 사용할 수 있습니다. Core 내부 초기화 구현은 SDK 인터페이스와 값 컨텍스트로 옮깁니다.

## 앱 요청 미들웨어

- 생성된 앱 라우트는 호스트의 테넌트 미들웨어를 직접 가져오는 대신 공개 `Nexia\Http\Middleware::APP_REQUEST` 진입부를 사용합니다. Core는 기존 웹·테넌트·세션·인증·위임 검사에 연결하며 앱 설치·리소스 문맥 검사는 유지합니다. 이 라우트를 생성하기 전에 대응하는 SDK/Core 릴리스를 함께 적용해야 합니다. 분리 런타임의 요청 인증은 별도 어댑터 연결이 필요합니다.

- 격리 앱 카탈로그가 `PlatformDescriptorWire`를 통해 결과 필드와 선택적 DMN 초기 규칙을 포함한 의사결정 결과 템플릿을 전달합니다. 대응 샌드박스 런타임을 함께 적용하면 Core에 앱 PHP를 설치하지 않고 작성 화면에서 템플릿을 사용할 수 있습니다.

## 표준 Runtime 선언

- Runtime 요구사항·선언 capability·앱 공통/Tenant별 설정 정의와 실행 미지원 상태를 표현하는 Action 계약을 추가합니다. 필수 미지원 기능과 연동은 거부하며, 선언만으로 설정 조회·비동기 작업·상태 변경을 개방하지 않습니다.

## Composer 호환성

PHP 패키지 이름은 `nexia/sdk-laravel`이며 같은 버전의 `amuzcorp/nexia-app-sdk-laravel`을 대체합니다. 루트 의존성을 새 이름으로 변경하세요. 기존 호환 앱의 요구 조건은 유지되며 앱 버전 범위나 lock이 자동으로 변경되지는 않습니다. `NexiaModel`은 기존 앱의 미디어 인터페이스를 유지합니다. Composer 호스트는 로컬 미디어를 명시적으로 활성화하고 격리 런타임은 비활성 상태를 유지합니다. 새 런타임 기능은 대응 Core·sandbox-manager 어댑터와 함께 적용하세요.
- 공통 의존성 정책과 등록된 목록 라우트 조회를 추가합니다. 운영 Runtime과 운영자 테스트 도구의 허용 목록을 구분하고, 앱의 운영 패키지 추가·선행 실행 autoload·타 앱 PHP 의존성을 거부합니다.
- 대응하는 Core·sandbox-manager 소스를 함께 적용해야 합니다. 기존 SDK 0.7.0 배포물에는 이번 초안 계약이 없으므로 릴리스 Runtime 빌드 전에 새 호환 SDK 배포물 게시와 채택이 필요합니다.

- 공통 메타데이터 읽기는 sandbox 호스트를 선택하지 않고 선언 형식을 검사합니다. sandbox 진입점은 앱 PHP를 로드하기 전에 미지원 Runtime 버전·필수 capability·integration을 계속 거부합니다. `platform`이 없는 기존 Composer 메타데이터 읽기는 유지하며, production 앱의 설치·부팅 호환성은 별도로 확인해야 합니다.
