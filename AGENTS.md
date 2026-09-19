# AGENTS.md

## Permanent branches

`main` and `develop` are permanent branch names. Never delete their local or
`origin/*` refs during cleanup, even when they appear merged.

Before creating an authorized PR/MR whose integration target is `develop`,
fetch the target repository and verify `origin/develop`. If the remote branch
is absent, recreate `develop` at the current `origin/main` commit through the
hosting provider API, verify the new remote ref, and then target the PR/MR to
`develop`. Treat this recovery as part of the authorized PR/MR creation; do not
retarget the change to `main`. Never move or force-update an existing
`develop`; report an unexpected divergence instead.
