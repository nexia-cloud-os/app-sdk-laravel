---
status: draft
---


- sandbox 카탈로그에서도 하이픈이 포함된 앱 식별자를 슬롯 위젯 component 키로 사용할 수 있습니다. Composer 등록과 동일하게 동작하며, 경로·URL·PHP 클래스명은 계속 거부합니다.
- 결재 대상자 선언과 서명된 callback의 SDK 전송 계약을 추가하여 sandbox에서도 소유 앱이 결재 대상자를 결정할 수 있습니다.

- `ResolvedResourceReference::fromSnapshot()`은 해시를 검증해 공개 스냅샷을 복원하고 보호된 값을 거부합니다. 쓰기 사전조건·선택 API 문서는 로컬 트랜잭션 잠금과 격리 실행의 완료 검증을 구분하며, 런타임을 가로지르는 공동 롤백을 보장하지 않습니다.

- 성과 측정 선언을 공통 플랫폼 전송 규격으로 직렬화·복원합니다. `PartyDirectory::requireByPublicIdForUpdate`, `OrganizationDirectory::lockLegalEntityByKey`, `lockOperatingUnit`을 제거했습니다. 공통 쓰기 사전조건이나 선언된 참조 선택 계약을 사용해야 하며, Core와 App 사이의 공동 트랜잭션은 보장하지 않습니다.

- App은 공용 SDK 카탈로그로 소유 비동기 작업 handler와 schedule을 선언할 수 있습니다. 설치·권한·전달·재시도·배포 세대 검증은 host가 담당하며 handler에는 검증된 invocation만 전달됩니다. 운영 작업을 선언하기 전 대응 Core queue·scheduler 연동을 함께 적용해야 합니다.

- App 메타데이터는 `nexia.json`의 schema version `2`, runtime `laravel` 선언이 필요합니다. Composer `extra.nexia` 선언과 중복 선언은 거부합니다. `nexia-cloud-os/sdk-laravel`을 요구해야 하며 이전 패키지명 대체 선언은 제거했습니다. 대응 Core·런타임 로더를 함께 적용해야 합니다.
- PHP `ResourceActionDescriptor` 별칭을 제거했습니다. 카탈로그 액션은 `ActionDefinition`으로 선언해야 합니다. React 드롭다운 액션 타입에는 영향이 없습니다.

- 선택 계약 `SignatureBulkBindingLifecycleProvider`로 대량 전자서명 제공자가 예약과 요청 접수·해제를 조정할 수 있습니다. [수명 주기 구현 가이드](https://developers.nexia.to/ko/docs/platform-extensions-electronic-signature)를 참고하세요.

## 내 팀 프로필의 조건부 표시

- API v2 `profile.team.overview` 배치와 선택적 `SlotWidgetDescriptor.availabilityResourceKey`를 추가합니다. 소유 앱이 같은 앱의 Resource Reference에서 `fields.visible`을 반환해 현재 사용자의 표시 대상을 판단합니다. 조회 실패 시 재시도를 표시하며, 기존 개인 슬롯과 null 필드를 생략한 전송 형태는 호환됩니다. 새 슬롯을 등록하기 전에 대응하는 PHP SDK와 Core 호스트를 함께 적용하세요. 업무 권한과 대상 범위 검사는 앱에서 계속 수행해야 합니다.


## 공통 문서 데이터

PHP에 용도별 문서 준비를 위한 `DocumentDataSourceDescriptor`,
`DocumentDataSourceContribution`, `DocumentDataHost`를 추가합니다. 중립 이름의
query/result는 기존 값 검증 계약을 재사용합니다. 기존 전자서명 소스는 명시적으로
새 용도를 추가하기 전까지 전자서명 전용입니다. 정확한 소스·버전·필드 연결을
저장하고 새 호스트를 사용하기 전에 대응하는 Core·런타임을 적용하세요.
출시 버전은 미정이며 자세한 내용은
[계약 가이드](https://developers.nexia.to/ko/docs/app-sdk-electronic-signature-contracts)에 있습니다.

## 리소스 참조 제공자의 그룹 일괄 조회

여러 날짜와 필터 그룹을 한 번에 조회하려는 제공자는 이 기능을 지원하는 런타임에서 선택적으로 `ResourceReferenceGroupedResolutionContribution`을 구현할 수 있습니다. 기존 일괄 조회 제공자는 그룹별 조회로 계속 동작합니다. 전달받은 사용자와 조직 범위 안에서 각 그룹의 날짜와 필터에 대한 권한을 검증하고, 거부된 참조는 제외하며 그룹 키와 기준 시각을 그대로 유지해야 합니다.
