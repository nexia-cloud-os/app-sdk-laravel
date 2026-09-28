---
status: draft
---

`ResourceDescriptor::integrationContract()`가 설치 앱과 격리 앱의 공통 연동 명세를 제공합니다. 내부 전용·제거된 리소스는 `null`을 반환하고 공개 조합용 이벤트만 포함합니다. 구현 경로를 노출하거나 조회·변경 권한을 부여하지 않습니다. 선택한 개발 앱의 명세를 조회하려면 대응하는 Core·샌드박스 런타임 변경을 함께 적용하세요.

## 추가

- 공개 `TenantAppInitializerContribution`, `VersionedTenantAppInitializerContribution`, 불변 `TenantAppInitializationContext`로 Core import 없이 App 필수 데이터를 초기화합니다. 대응 Core 설치기가 contribution을 발견하고 App 소유권과 완료 상태를 검사합니다. 컨텍스트는 Core 모델 대신 definition·tenantId·initializationVersion을 제공합니다. 초기화는 멱등해야 하며 외부 부작용을 넣지 않습니다.
- 선택적 `ResourceDescriptor::publicForIntegration`으로 Builder 공개 여부와 별개로 개발자용 연동 계약을 공개합니다. 생략하면 기존 공개 범위를 유지합니다. 명세 공개는 실행 권한을 부여하지 않습니다.
- DB 검색만 제공하는 Laravel 호스트는 `ScoutSearchResolverRegistry::configure`에 identity resolver를 `null`로 전달하고 실제 Scout engine resolver를 연결할 수 있습니다. 기존 호스트의 identity resolver는 그대로 유지합니다. 공유 테넌트 검색을 활성화하는 기능은 아닙니다.

## 적용

대응 Core 초기화·계약 조회 변경과 tenant migration `2026_09_23_060000_track_app_initialization_application`을 함께 적용합니다. 기존 App manifest와 ResourceDescriptor 호출은 그대로 사용할 수 있습니다. Core 내부 초기화 구현은 SDK 인터페이스와 값 컨텍스트로 옮깁니다.

## 앱 요청 미들웨어

- 생성된 앱 라우트는 호스트의 테넌트 미들웨어를 직접 가져오는 대신 공개 `Nexia\Http\Middleware::APP_REQUEST` 진입부를 사용합니다. Core는 기존 웹·테넌트·세션·인증·위임 검사에 연결하며 앱 설치·리소스 문맥 검사는 유지합니다. 이 라우트를 생성하기 전에 대응하는 SDK/Core 릴리스를 함께 적용해야 합니다. 분리 런타임의 요청 인증은 별도 어댑터 연결이 필요합니다.
