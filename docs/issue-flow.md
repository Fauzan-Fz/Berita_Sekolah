# Issue-Flow — autonomous dev loop (manual trigger)

## Use
- `/issue-flow 12` — run the workflow for Issue 12.
- `/issue-flow keranjang belanja` — find the matching open Issue, or create one, then run.
- One Issue = one branch (`issue-<n>-<slug>`) = one PR (`Closes #n`). PRs stay OPEN; merge is manual.

## Resume
- Re-run `/issue-flow <same arg>` in a new session. The coordinator reads `.orca/runs/<n>.json`, verifies Run/tasks/dispatches/branch/PR against reality, reuses everything live, and never resets the fix counter.

## Limits (v1)
- Trigger is manual; no auto-run on Issue creation or CI completion.
- Max 3 automatic fix rounds, then a blocker report on the main Issue.
- PASS requires CI success + QA PASS + Security clean on the same HEAD sha.
- State lives in git-ignored `.orca/runs/`; docs and spec are committed, the skill file itself is local-only in v1.
- After a fresh clone, `/issue-flow` does NOT exist until the skill file is recreated — known v1 limitation, not auto-available.
