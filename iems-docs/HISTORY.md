# HISTORY

Reconstructed from `git log` of `v222-iems-dev` (HEAD 1c1c62364 at writing), `IEMS-CHANGES.md`, the IEMS README text (`iems-docs/README.md`; formerly in the root README, commits 9e100688d and 80a9c5ca8) and the 2026-07-16 handoff. Dates are commit dates. Items without a hash are from README prose and are **unverified** against git.

## Before this repository's 2.2.2 fork (README, unverified)
- 2012 or 2013: IEMS Zen Cart installations commissioned; five custom installations; managed outside version control until 2018.
- 2019: placed under version control with v1.5.6 (still in production per README).
- 2022-2023: v1.5.7 and v1.5.8 development; stopped June 2023.
- June 2026: 2.2.2 fork development began (README). First fork-specific commits in git are dated 2026-07-07 (below).

## Timeline (verified from git)
| Date | Commit | Event |
|------|--------|-------|
| 2026-07-07 | 6973f37a8, 3c783aac9 | Feature-test workflows and support files synced with upstream 2.2 |
| 2026-07-07 | 9b454992e | Fix `buy_now` referer host matching for CI port |
| 2026-07-07 | 5e35fd685 | Import IEMS template files from v300 branch |
| 2026-07-07 | d731857df, 395e35ca5, e77e2bece, 8acdbc3ed, 0a709bddc | Import DbIo, Edit Orders, One-Page Checkout, Priority Handling, Postcode Auto Fill plugin sources |
| 2026-07-08 | 975ffeafd | Import ZCA Bootstrap Template v3.8.0 |
| 2026-07-08 | 7f1583a7c, b6f3c5f87, 8ce7f1df9, 35bb4b4b5, 75f9dff0f | Plugin branches merged into `v222-iems-dev` |
| 2026-07-08 | 0b2773b1b, 8da1b2d40, c1da5e252, ddd29d73a, b56db83ea | `is_guest_order` guard, seeder refactor, LowOrderFeeTest isolation |
| 2026-07-09 | f054d1e3e | Local Docker feature-test procedure documented |
| 2026-07-11 | 3e8b8d6ba .. 43edd8df1 | Template inheritance experiments; replaced by standalone IEMS template copy (43edd8df1) |
| 2026-07-16 | b796b3a72 | IEMS handoff document added (now `handoffs/2026-07-16-...`) |
| 2026-07-16 | b1b5b5ac0, 38eb7f98a, 0e16e0f2b, 8f7351638, 88097e60b | County/agency/unit foundation schema; legacy agency and Marion County unit imports; `iems_data.md` |
| 2026-07-17 | 0b6dcca32 | First `IemsCountyAgency` plugin commit ("branch 2 work"); hotfixes 166690b24, 8d8c3deef |
| 2026-08-01 | 5cc68901a, ae02de400, aeba260a8, f7502b153 | Customer affiliation self-edit; admin-configurable account-edit lock (v1.1.0); units import completed |
| 2026-08-01 | 5606328a3 | `IEMS-CHANGES.md` added; upstream/2.2 merged (e2b325505) |
| 2026-08-03 | 3d2c0759d, 0d37beb04, fd5415a1c, 058c0dea4, 5fb363d44 | Agency management v1.2.5, profile authorization v1.3.0, unit management v1.4.0, Edit Orders zen_config fix |
| 2026-08-05 | 9e100688d, 80a9c5ca8 | README project history and EMS reference |
| 2026-09-18 | 89963d285 | v1.5.0 mandatory per-order unit selection |
| 2026-09-18 | 590f90252 | v1.5.5 agency fallback for agencies with zero active units |
| 2026-09-18 | b8e36d881 | v1.6.0 delivery flag; `iemspickup`/`iemsdelivery` shipping modules |
| 2026-09-18 | de1a8e232, 099d832dc, 913c9b045 | v1.7.0 managed unit delivery addresses; checkout-loop fix; shipping prerequisites documented |
| 2026-09-19 | aa2f60f66, 4a7af2d34 | v1.8.0 agency payment methods; shipping model extension merged (v1.9.0 content per IEMS-CHANGES; commit mapping unverified) |
| 2026-09-19 | 0e7088437 | v1.9.1 checkout presentation ("Ordering Unit") |
| 2026-09-19 | d84cfb3ec, 18c5c3394, 1d570644e | v1.9.2 delivery identity/address |
| 2026-09-19 | 2341ccab9, 62bb25742, e26092de1 | v1.9.4 admin order address display added, reverted, compatibility releases restored (v1.9.4/v1.9.5) |
| 2026-09-19 | c42e564d7, 979db3ecd, 20f662fcb | Direct `admin/orders.php` delivery-suburb edits (later replaced by v1.9.6) |
| 2026-09-19 | efe18e542 | IEMS reporting roadmap documented in handoff |
| 2026-10-22 | 64cf51778 | v1.9.6: Orders-list unit display moved to `zcObserverIemsOrdersList`; `admin/orders.php` restored to stock (PR #59 merged 2026-10-31, 230115eae) |
| 2026-10-31 | bf41a5eee | `wombat/` and `YOUR_ADMIN/` merged into `admin/` (PR #61, 643a7d082) |
| 2026-11-06 | f089ec107 | Password-reset link opens on login-to-browse stores (PR #62, 1c1c62364) |

## Version to commit map (IemsCountyAgency)
v1.1.0 ae02de400; v1.2.5 3d2c0759d; v1.3.0 0d37beb04; v1.4.0 058c0dea4; v1.5.0 89963d285; v1.5.5 590f90252; v1.6.0 b8e36d881; v1.7.0 de1a8e232; v1.8.0 aa2f60f66; v1.9.0 unverified (shipping model extension, merge 4a7af2d34); v1.9.1 0e7088437; v1.9.2 18c5c3394; v1.9.4/v1.9.5 e26092de1; v1.9.6 64cf51778. v1.0.0 and v1.2.0 etc.: not located (unverified).

## Open items
See `TODO.md` and "Open questions" in `DECISIONS.md`.
