# IEMS Adaptation Handoff (2026-07-16)

## Working constraints (must persist)
- **Approval gate:** No code/file changes without explicit user approval first.
- **Plan-first delegation:** Any delegated session/sub-agent should propose a plan and wait for approval before implementing.
- **Architecture preference:** Use **template/plugin** approaches; avoid Zen Cart core edits unless truly unavoidable, and flag any core-touch explicitly.
- **Environment assumption:** This is **development-only** work (no live production deployment yet). Reconfirm if this changes.

## Functional requirements agreed so far
1. Ordering context hierarchy is: **county -> agency -> unit**.
2. Legacy use of `suburb` (ambulance/fire house context) is being replaced by explicit hierarchy tracking.
3. Downstream/custom IEMS datasets are expected as:
   - `iems-counties`
   - `iems-agencies`
   - `iems-units`
4. Account setup:
   - Customer selects `county` and `agency` during initial account creation.
   - Customer cannot later change those values.
   - Only superusers may modify county/agency after initial setup.
5. `unit` is **order-specific** (can change each order), not permanently fixed per account.
6. Address book mapping should be **agency/unit-centric**, not individual-customer-centric.
7. Customer grouping:
   - Customer group is derived from `county + agency`.
   - Product rules are enforced by customer group:
     - product enabled/disabled per group
     - min quantity per product/group
     - max quantity per product/group

## Incremental branch plan (apply one branch at a time)

### Branch 1: Data model + admin foundations (no storefront behavior change yet)
**Suggested name:** `iems-county-agency-unit-foundation`

Scope:
- Define/introduce persistence mapping for county/agency/unit and group linkage using plugin-first patterns.
- Add admin-maintained structures for county/agency/unit relationships.
- Define deterministic mapping from county+agency to customer-group.
- Add superuser-only capability model for post-creation county/agency edits.

Acceptance focus:
- Data structures exist and are manageable in admin paths.
- No customer-visible flow changes yet.

---

### Branch 2: Account creation + immutability enforcement
**Suggested name:** `iems-account-county-agency-locking`

Scope:
- Add county and agency selection to registration/create-account flow.
- Enforce agency options filtered by county.
- Persist county/agency on first creation.
- Prevent customer self-service changes after creation.
- Allow superuser override path only.

Acceptance focus:
- New account successfully captures county+agency.
- Non-superuser cannot modify them later.
- Superuser can modify them through authorized path.

---

### Branch 3: Order-time unit selection + address model shift
**Suggested name:** `iems-order-unit-and-address-mapping`

Scope:
- Add unit selection to ordering flow, filtered by selected agency.
- Make unit selection per-order.
- Adapt address-book behavior so entries are linked to agency/unit model rather than individual ownership assumptions.

Acceptance focus:
- Unit can vary across successive orders.
- Address selection and persistence align with agency/unit strategy.

---

### Branch 4: Customer-group assignment + catalog gating
**Suggested name:** `iems-group-driven-product-rules`

Scope:
- Auto-assign/maintain customer group from county+agency.
- Enforce per-product group enable/disable.
- Enforce per-product group min/max ordering quantities.
- Ensure enforcement is consistent across listing, product page, cart, and checkout validation.

Acceptance focus:
- Users only see/order products allowed for their group.
- Quantity boundaries are enforced consistently end-to-end.

---

### Branch 5: Hardening + migration + operator documentation
**Suggested name:** `iems-rollout-hardening-and-migration`

Scope:
- Migration/backfill strategy for existing customers and legacy `suburb` usage.
- Admin QA checklist and exception handling paths.
- Audit/reporting hooks for county/agency/group assignment anomalies.
- Final documentation for support/admin operations.

Acceptance focus:
- Legacy data transition path is explicit and testable.
- Operational team can manage and troubleshoot with clear runbook steps.

## Cross-branch guardrails
- Keep each branch narrowly scoped and independently testable.
- No core edits unless plugin/template route is proven insufficient; if core edit is required:
  1. document why alternatives failed,
  2. isolate change surface,
  3. annotate risk and rollback approach.
- Prefer additive schema and reversible migrations during early rollout.
- Keep a clear compatibility plan for existing accounts during transition.

## Copy/paste kickoff prompt for future sessions (plan-first)
Use this prompt to start the next coding session:

> We are continuing IEMS adaptation work on `jrcook416/zencart` from `v222-iems-dev`.  
> **Important constraints:**  
> - Do not make code changes until you present a plan and I approve it.  
> - Prefer template/plugin approaches; avoid core edits unless unavoidable and explicitly justified.  
> - Assume development-only environment (no production deployment yet).  
>  
> **Current requirements:**  
> - Hierarchy: county -> agency -> unit  
> - Data sources: `iems-counties`, `iems-agencies`, `iems-units`  
> - Account creation captures county + agency; customer cannot change later; superuser can  
> - Unit is selected per order  
> - Address logic should map to agency/unit, not individual-customer-centric  
> - Customer group derives from county+agency  
> - Per-product customer-group controls: enabled/disabled, min qty, max qty  
>  
> Start with branch: `<INSERT TARGET BRANCH FROM HANDOFF PLAN>`.  
> Provide an implementation plan, impacted files/components, data changes, and test strategy. Wait for approval before coding.

