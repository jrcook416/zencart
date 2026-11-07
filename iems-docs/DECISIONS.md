# DECISIONS

Single home for rulings and standing constraints. Each entry cites its source. Nothing here is newly decided; entries were lifted from existing files. "Approval" of any entry by a named person is **unverified** unless the source says so.

## Standing constraints
| # | Decision | Source |
|---|----------|--------|
| D1 | No code/file changes without explicit user approval first. | handoffs/2026-07-16 "Working constraints" |
| D2 | Delegated sessions/sub-agents propose a plan and wait for approval. | same |
| D3 | Prefer template/plugin approaches; avoid Zen Cart core edits; flag any core touch. | same; reinforced by 64cf51778 (core `admin/orders.php` edit replaced by observer in v1.9.6) |
| D4 | Ask for clarification before implementing ambiguous requirements or unresolved reporting rules. | same |
| D5 | ~~Development-only work (no live production deployment yet).~~ Superseded by D9. | handoffs/2026-07-16 "Working constraints" (dated 2026-07-16) |
| D6 | Do not auto-commit; stage changes and summarize; the developer commits. | CLAUDE.md |
| D7 | Never push or deploy by assistants. | owner preference for this docs work; not in repo files before this entry |
| D8 | Coding standards: see CONVENTIONS.md (PSR-12, strict_types, legacy exceptions). | CONVENTIONS.md |
| D9 | The 2.2.2 revision WITHOUT the multi-store work is live in production. Treat changes accordingly; never deploy from assistants. | Jeremiah, 2026-11-07 (relayed via General Chat session) |
| D10 | All IEMS documentation lives in `iems-docs/`. AGENTS.md and CLAUDE.md are directions for IEMS use only and stay there, with no root stubs. `docs/` is upstream-only. | Jeremiah, 2026-11-07 (relayed via General Chat session) |

## Functional rulings (from the 2026-07-16 handoff, "Functional requirements agreed so far")
- F1: Ordering hierarchy county -> agency -> unit; `suburb` replaced by explicit hierarchy.
- F2: Customer picks county/agency at account creation; cannot change later; only superusers may change. (Later made admin-configurable lock: ae02de400, v1.1.0.)
- F3: Unit is order-specific, not fixed per account.
- F4: Address book mapping is agency/unit-centric.
- F5: Customer group derives from county + agency; product enable/disable, min and max quantity enforced per group (not yet built; see TODO).

## Implementation rulings recorded in code history
- R1: v1.9.4 admin order-detail address observer withdrawn and not reintroduced (62bb25742; IEMS-CHANGES v1.9.6).
- R2: v1.9.4 is a compatibility recovery; v1.9.5 is the supported forward version (IEMS-CHANGES; e26092de1).
- R3: Zero-unit agency fallback is pickup-only; never qualifies for Delivery to Location (handoff v1.7.0 checks).
- R4: Agency uninstall retains IEMS data and configuration (IEMS-CHANGES v1.4.0).

## Open questions (unverified; need Jeremiah)
1. ~~Handoff recipient.~~ Resolved 2026-11-07: "future-sessions" is correct for the 2026-07-16 handoff.
2. ~~Is the project still development-only?~~ Resolved 2026-11-07: no; see D9.
3. ~~v1.9.0 commit mapping.~~ Resolved from git: version directory added in a631f4717, merged by 4a7af2d34 (see HISTORY).
4. ~~Early plugin versions.~~ Resolved from git: v1.0.0 0b6dcca32, v1.2.0 00c2433f0 (all 17 versions mapped in HISTORY). v1.3.5 does not exist as a version.
5. Pre-2026 history: partly resolved. Git shows only upstream Zen Cart tags plus one Jeremiah-authored commit (tag `v157d-EO`, 35038957e, 2022-04-08, not an ancestor of this branch). README claims (2012/2013 start, 2019 version control, 1.5.7/1.5.8 work, 2023 stop) remain unverified. What does "EO" in `v157d-EO` mean?
6. Are formal roles (like the training repo's Rob/Frank) wanted here, or only owner + assistants?
7. ~~Move IEMS docs into `iems-docs/`?~~ Resolved 2026-11-07: all moved (see D10); the stale path comment in `zc_install/sql/iems_custom/iems_foundation.sql` was fixed (e458a4d4a).
8. ~~Is `docs/iems` upstream or fork-authored?~~ Resolved: fork-authored (Jeremiah Cook, commits 571d469c0..43edd8df1, 2026-07-11), no references in code or links; moved to `iems-docs/iems-template-readme.html` (3adc2c4e1). `docs/bootstrap` is the imported lat9 ZCA Bootstrap template documentation (975ffeafd) and was left in place.
9. The README sentence "Version 1.5.6 remains in production today" is stale per D9; the root-README text was moved into `iems-docs/README.md` unchanged. Edit it?
