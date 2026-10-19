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
