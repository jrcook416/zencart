# DECISIONS

Single home for rulings and standing constraints. Each entry cites its source. Nothing here is newly decided; entries were lifted from existing files. "Approval" of any entry by a named person is **unverified** unless the source says so.

## Standing constraints
| # | Decision | Source |
|---|----------|--------|
| D1 | No code/file changes without explicit user approval first. | handoffs/2026-07-16 "Working constraints" |
| D2 | Delegated sessions/sub-agents propose a plan and wait for approval. | same |
| D3 | Prefer template/plugin approaches; avoid Zen Cart core edits; flag any core touch. | same; reinforced by 64cf51778 (core `admin/orders.php` edit replaced by observer in v1.9.6) |
| D4 | Ask for clarification before implementing ambiguous requirements or unresolved reporting rules. | same |
| D5 | Development-only work (no live production deployment yet); reconfirm if this changes. | same (dated 2026-07-16; current status unverified) |
| D6 | Do not auto-commit; stage changes and summarize; the developer commits. | CLAUDE.md |
| D7 | Never push or deploy by assistants. | owner preference for this docs work; not in repo files before this entry |
| D8 | Coding standards: see CONVENTIONS.md (PSR-12, strict_types, legacy exceptions). | CONVENTIONS.md |

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
1. Who were the intended sender/recipient of the 2026-07-16 handoff? Renamed `jeremiah-to-future-sessions` from the commit author and its kickoff prompt; recipient is an assumption.
2. Is the project still development-only (D5)?
3. Is v1.9.0 mapped to merge 4a7af2d34 correctly (no single release commit found)?
4. Which IemsCountyAgency versions lack git commits (v1.0.0, v1.2.0, v1.3.5, etc.)?
5. Should HISTORY include pre-2026 events (README prose is unverified)?
6. Are formal roles (like the training repo's Rob/Frank) wanted here, or only owner + assistants?
7. ~~Should `IEMS-CHANGES.md` and `iems_data.md` move into `iems-docs/`?~~ Resolved 2026-11-07 by Jeremiah: AGENTS.md, CONVENTIONS.md, CLAUDE.md, IEMS-CHANGES.md and iems_data.md all moved into `iems-docs/`. Side effect: tools that look for AGENTS.md/CLAUDE.md at the repo root will not find them; `zc_install/sql/iems_custom/iems_foundation.sql` still mentions `iems_data.md` by old path (code not changed).
8. Is the `bootstrap`/`iems` template readme under `docs/` upstream-derived or fork-authored? Left untouched.
