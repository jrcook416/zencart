# Handoff: Copilot to Jeremiah, docs alignment (2)

Date: 2026-11-07
Repo state: base commit 64527aa03, further changes staged locally on branch jrcook416-zencart-docs-alignment (not committed, not pushed)

Handoffs are a dated record. Do not edit after sending; write a new one. Live task state is in `iems-docs/TODO.md`; decisions belong in `iems-docs/DECISIONS.md`.

## Context
Follow-up to the first docs-alignment handoff. At your direction, the remaining project docs moved out of the repo root.

## Done
- `git mv` of AGENTS.md, CONVENTIONS.md, CLAUDE.md, IEMS-CHANGES.md and iems_data.md into `iems-docs/` (history preserved).
- Updated the index and cross-links; resolved open question 7 in `DECISIONS.md`.

## Needed from you
- Decide whether a root stub for AGENTS.md/CLAUDE.md is wanted (tools look for them at the root); see `TODO.md`.
- `zc_install/sql/iems_custom/iems_foundation.sql` mentions `iems_data.md` by old path in a comment; code was not changed.

## Do not
- Do not edit the earlier handoffs.

## Open questions
- See `DECISIONS.md`.
