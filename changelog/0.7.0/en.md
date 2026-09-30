---
status: published
version: 0.7.0
date: 2026-09-30
title: Email, import recovery and process contracts
description: Contracts for connected processes and resumable imports, with an updated approval host interface.
---

## Upgrade required

- Laravel and React SDK 0.7.0 are adopted with Core 0.6.21 or later in the 0.6 line. Consumer Apps must declare compatible SDK ranges.
- Custom ApprovalIdempotency implementers must add the optional prepare closure and preserve replay-first preparation outside the write transaction. This breaking change requires a minor release. Existing two-argument callers remain supported.

## Added

- `renderPlanChoices` receives optional `legalEntityPublicId` and `referenceIssues` from the active preview. Apps can keep source-specific prerequisite setup in the import review; existing renderers remain compatible. Requires a Core host that supplies these fields.
- Migration stages can declare optional `fileGuideSteps` with localized titles, sections and a separate `noteKey` for provider-specific file preparation. The registering App owns the content; a matching Core host renders the ordered steps. Existing registrations keep the host's default guide.
- `DataMigrationStageProps.draftKey` lets the host separate a fresh file upload from an existing review draft. App stages that restore local state should include this optional key in their draft identity.
- The dataset import `renderPlanChoices` context exposes `recheck()` and reports analysis in `busy`, so App reviews can refresh restored results after their review rules change. Requires a matching Core host.
- Dataset import workspaces accept `allowRowEditing={false}` to hide source-row correction actions and editors. Editing stays enabled by default. Requires a matching Core host.
- `useDataMigrationBackgroundOperation().wait()` accepts `continueInBackground: true` to keep observing a submitted operation after its screen unmounts. The default still cancels the wait on unmount; caller signals and `cancelAll()` continue to cancel explicitly retained waits. Callers own checkpoint restoration and must avoid navigating a different active task from a background completion.
- Dataset import workspaces support `referenceReviewPlacement="after_preview"` to place reference setup below the complete data preview. Requires a matching Core host.
- Dataset import workspaces support `confirmationSelection` to promote a multiple-choice decision to a selection dialog and confirm its normalized selection before applying. Migration stages can declare ordered `resultColumnKeys` for the saved-result table. Both require a matching Core host.
- `ProcessTemplateDescriptor` accepts an optional `startResourceKey` for a manually started App process. The key must name a declared Resource dependency. Hosts use it to require an authorized business record at start.
- `ShellResourceDescriptor` accepts `contextualCreate` to advertise a canonical create form that reports its new Resource through the Shell continuation.
- Slot-backed `ProcessUserTaskFormDescriptor.outputSchema` accepts the typed `ResourceRef` output when a task passes a canonical business record to later BPMN steps.
- Laravel SDK import pipelines can declare optional `templateExampleRows`, keyed by importable schema columns. Core hosts that adopt this contract include the examples in CSV and XLSX template downloads. Existing pipelines keep header-only templates.
- Background migration operation status now includes `cancelled`; the shared wait helper stops polling when the host reports it.
- App-neutral email contribution contracts support versioned starter templates, authorized business values, recipient selectors, and tenant/Legal Entity execution context. Hosts must bind `EmailContributions` before Apps register providers.
- The dataset import review contract can request inline reference creation, including a sequence of missing references, a full-batch reference summary, and the target Legal Entity. Hosts must bind `NxInlineResourceCreate` for Apps that use it.
- Dataset import workspaces can render App-owned plan choices and summaries while keeping the host's preview, decision submission, and commit flow. Reference setup can follow plan choices when those choices determine what must be created. Apps using this slot require a matching Core host.
- Inline Resource creation reports its presentation and returns the created Resource identity to the caller. Apps can keep a parent form open while creating a required reference inside it. This requires a matching Core host.
- App migration stages can report transient file analysis and saving activity through `DataMigrationStageProps.onActivityChange`, allowing the Core choice list to show and reopen ongoing work. This requires a matching Core host.
- React SDK exports `NxSectionRow`, `NxSwitch`, and their prop types through the public host contract, reusing Core's shared settings row and on/off controls.
- `ApprovalIdempotency::execute` accepts an optional `prepare` closure. A supporting Core host runs it under the request lock after checking replay, before opening its write transaction, and passes the prepared result to the callback. Replays skip preparation; business writes and replay metadata remain atomic.

## Adoption notes

- `DataMigrationStageProps.resumeOperation` supplies the analysis or import operation id selected by the host, with an optional attempt identity. Apps restore that server result and handle expired or failed operations. Adopt the Core host that supplies it together with the consuming App.
- Apps using `templateExampleRows` require a Laravel SDK release containing this contract and a Core host that renders the rows. Do not enable the declaration against older installed SDK versions.
- Hosts must bind the new `NxSectionRow` and `NxSwitch` components before Apps consume them. Use SDK 0.7.0 with Core 0.6.21 or later in the 0.6 line.
- Existing two-argument idempotency callers remain unchanged. Custom `ApprovalIdempotency` implementations must add `?Closure $prepare = null` and honor the preparation/transaction contract. Apps consuming preparation require both the new Laravel SDK and the implementing Core host; use SDK 0.7.0 and Core 0.6.21 or later in the 0.6 line.
