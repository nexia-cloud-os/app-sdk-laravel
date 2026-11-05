---
status: published
version: 0.10.21
date: 2026-11-05
title: Frozen user-task context
description: Use host-provided task context in App submissions.
---

`ProcessUserTaskSubmissionInvocation` now includes optional `taskContext`, supplied separately from form input and defaulting to an empty array for existing callers. Validate the item, its ownership, prerequisites and the actor’s permission in the App. Adopt matching Core and App runtime support before requiring this context. See the [Business Process contracts](https://developers.nexia.to/docs/app-sdk-business-process-contracts) and [published SDK contract](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.21/src/Process/ProcessUserTaskSubmissionInvocation.php).
