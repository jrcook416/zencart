# Handoff: docs alignment, round 3 (history verification and rulings)

Date: 2026-11-07
From: Copilot
To: Jeremiah
Repo state: branch `jrcook416-zencart-docs-alignment`, based on 3adc2c4e1 (not pushed)

## Context
Jeremiah ruled on six items (no root stubs, fix stale SQL comment, verify history, recipient confirmed, production status, no `docs/iems`).

## Done
- e458a4d4a: fixed the `iems_data.md` path in a comment in `zc_install/sql/iems_custom/iems_foundation.sql` (comment only).
- 3adc2c4e1: moved `docs/iems/readme.html` (IEMS-authored, no references) to `iems-docs/iems-template-readme.html`. `docs/bootstrap` left (imported lat9 ZCA Bootstrap docs).
- HISTORY.md: full IemsCountyAgency version-to-commit table (v1.0.0 to v1.9.6); v1.9.0 = a631f4717; pre-2026 findings.
- DECISIONS.md: D5 superseded; D9 (production live) and D10 (no root stubs, IEMS docs in `iems-docs/`) added; questions 1-4, 7, 8 resolved.

## Needed from you
- Answer open questions 5, 6 and 9 in DECISIONS.md.

## Do not
- Edit this record; write a new dated handoff instead.
- Push or deploy.

## Open questions
- Production go-live date and commit are not in git (D9 is Jeremiah-stated).
- Meaning of `v157d-EO` and whether pre-2026 README history is accurate.
- Formal roles beyond owner + assistants.
- Edit the stale "1.5.6 remains in production" README sentence?
