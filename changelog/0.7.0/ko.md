---
status: published
version: 0.7.0
date: 2026-09-30
title: 이메일·이관 복구·프로세스 계약
description: 업무 레코드 연결과 이관 복구 계약 및 결재 호스트 인터페이스 변경입니다.
---

## 업그레이드 필요

- Laravel 및 React SDK 0.7.0은 Core 0.6.21 이상 0.6 계열 호스트와 함께 적용합니다. 소비 앱은 호환되는 SDK 범위를 선언해야 합니다.
- ApprovalIdempotency 사용자 정의 구현체는 선택적 prepare 클로저를 추가하고, 재시도 확인 후 쓰기 트랜잭션 밖에서 준비하는 계약을 구현해야 합니다. 이 변경으로 minor 버전을 올립니다. 기존 인수 두 개 호출은 유지됩니다.

## 추가

- 이관 단계에 원본별 파일 준비 안내인 선택 속성 `fileGuideSteps`를 선언할 수 있습니다. 등록 앱이 번역 키로 단계 제목과 설명 구역, 별도 참고사항 `noteKey`를 제공하고 대응하는 Core 호스트가 순서대로 표시합니다. 기존 등록은 호스트의 기본 안내를 유지합니다.
- `DataMigrationStageProps.draftKey`로 호스트가 새 파일 업로드를 기존 검토 초안과 분리할 수 있습니다. 로컬 상태를 복원하는 App 이관 화면은 이 선택적 키를 초안 식별자에 포함해야 합니다.
- 데이터셋 가져오기 `renderPlanChoices`에 `recheck()`와 분석 중 `busy` 상태를 제공하여 App 검토 규칙 변경 시 복원한 결과를 다시 분석할 수 있습니다. 대응하는 Core 호스트가 필요합니다.
- 데이터셋 가져오기에서 `allowRowEditing={false}`로 원본 행 수정 버튼과 편집기를 숨길 수 있습니다. 기본값은 편집 허용이며 대응하는 Core 호스트가 필요합니다.
- 데이터셋 가져오기에서 `referenceReviewPlacement="after_preview"`로 기준정보 설정을 데이터 미리보기 아래에 배치할 수 있습니다. 대응하는 Core 호스트가 필요합니다.
- 데이터셋 가져오기에서 `confirmationSelection`으로 복수 선택 항목을 선택 팝업으로 표시하고 유효한 선택값을 반영 전에 다시 확인할 수 있습니다. 이관 단계는 `resultColumnKeys`로 저장 결과 표의 열과 순서를 선언할 수 있습니다. 두 기능 모두 대응하는 Core 호스트가 필요합니다.
- 수동으로 시작하는 앱 프로세스용 `ProcessTemplateDescriptor::startResourceKey` 선택 항목을 추가했습니다. 이 키는 선언된 Resource 의존성을 가리켜야 하며, 호스트는 시작 시 권한이 있는 실제 업무 건을 요구하는 데 사용합니다.
- `ShellResourceDescriptor::contextualCreate`를 추가해 앱의 정식 생성 화면이 Shell 복귀 흐름으로 새 리소스를 전달할 수 있음을 선언합니다.
- 슬롯 기반 `ProcessUserTaskFormDescriptor.outputSchema`에서 정규 업무 건 참조를 다음 BPMN 단계로 전달하는 `ResourceRef` 출력 타입을 지원합니다.
- Laravel SDK의 가져오기 파이프라인에서 가져올 수 있는 스키마 열을 키로 하는 선택적 `templateExampleRows`를 선언할 수 있습니다. 이 계약을 적용한 Core는 CSV·XLSX 템플릿 다운로드에 예시 행을 포함합니다. 기존 파이프라인은 헤더만 있는 템플릿을 유지합니다.
- 백그라운드 데이터 이관 작업 상태에 `cancelled`를 추가하고, 호스트가 중단 상태를 반환하면 공통 대기 함수가 조회를 멈춥니다.
- 버전이 있는 메일 기본 양식, 권한을 확인한 업무 정보와 수신자 선택, 테넌트·법인 실행 문맥을 위한 App 중립 이메일 기여 계약을 추가했습니다. App이 제공자를 등록하기 전에 호스트가 `EmailContributions`를 바인딩해야 합니다.
- 데이터셋 가져오기 검토에서 누락된 기준정보를 같은 화면에 생성하고 여러 건을 연속 설정하도록 요청할 수 있습니다. 전체 파일의 기준정보 누락 요약과 대상 법인도 전달합니다. 이 기능을 사용하는 App을 위해 호스트가 `NxInlineResourceCreate`를 바인딩해야 합니다.
- 데이터셋 가져오기에서 App이 계획 선택과 요약 화면을 직접 표시하면서도 호스트의 미리보기·선택 제출·저장 흐름을 사용할 수 있습니다. 선택에 따라 필요한 기준정보 설정을 계획 바로 아래에 배치할 수도 있습니다. 이 슬롯을 사용하는 App에는 대응하는 Core 호스트가 필요합니다.
- 인라인 Resource 생성에서 현재 표시 방식을 알리고 생성된 Resource 식별값을 호출 화면에 전달합니다. App은 필요한 기준정보를 생성하는 동안 상위 입력폼을 유지할 수 있습니다. 대응하는 Core 호스트가 필요합니다.
- 앱 이관 화면이 `DataMigrationStageProps.onActivityChange`로 파일 분석과 저장 진행 상태를 전달해 Core 작업 목록에서 진행 중인 일을 표시하고 다시 열 수 있습니다. 대응하는 Core 호스트가 필요합니다.
- React SDK가 공개 호스트 계약으로 `NxSectionRow`, `NxSwitch`와 해당 속성 타입을 내보냅니다. 기존 Core의 설정 행과 켜기·끄기 컨트롤을 재사용합니다.
- `ApprovalIdempotency::execute`에 선택적 `prepare` 콜백을 추가합니다. 이를 지원하는 Core는 요청 잠금 아래 재시도 결과를 확인한 뒤 쓰기 트랜잭션을 열기 전에 준비 단계를 실행하고, 그 결과를 실행 콜백에 전달합니다. 재시도 시 준비는 생략하며 업무 상태와 재시도 기록은 함께 커밋됩니다.

## 적용 안내

- `DataMigrationStageProps.resumeOperation`은 호스트가 다시 여는 분석 또는 저장 작업 번호와 선택적 시도 식별자를 전달합니다. App은 해당 서버 결과를 복원하고 만료·실패를 안내해야 합니다. 이를 전달하는 Core와 처리하는 App을 함께 적용하세요.
- `templateExampleRows`를 사용하는 앱에는 이 계약이 포함된 Laravel SDK 릴리스와 예시 행을 출력하는 Core가 모두 필요합니다. 이전 SDK 설치 버전에는 이 선언을 사용하지 마세요.
- 앱이 해당 컴포넌트를 사용하기 전에 호스트가 `NxSectionRow`, `NxSwitch`를 연결해야 합니다. SDK 0.7.0과 Core 0.6.21 이상 0.6 계열을 함께 사용합니다.
- 기존 두 인자 멱등 실행 호출은 그대로 유지됩니다. 별도 `ApprovalIdempotency` 구현은 `?Closure $prepare = null`을 추가하고 준비·트랜잭션 계약을 준수해야 합니다. 준비 단계를 사용하는 앱에는 새 Laravel SDK와 이를 구현한 Core가 모두 필요하며, SDK 0.7.0과 Core 0.6.21 이상 0.6 계열을 함께 사용합니다.
