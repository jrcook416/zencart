# IEMS Adaptation Handoff (2026-07-16)

## Status update (2026-09-19)

- The county/agency/unit schema and legacy imports are complete.
- Storefront registration, customer account affiliation handling, the default-enabled account-edit lock, and admin customer affiliation editing are complete.
- Admin agency and unit management with native Admin Profiles authorization is complete through IemsCountyAgency v1.4.0.
- IemsCountyAgency v1.5.0 adds mandatory per-order unit selection, standard and One-Page Checkout enforcement, order confirmation display, transient cart-bound state, and order-only persistence to the three existing suburb columns.
- IemsCountyAgency v1.5.5 preserves mandatory active-unit selection and adds one explicit agency fallback only for a valid active agency with zero active units. The fallback uses a fixed server token, live hierarchy/unit revalidation, transient typed selection state, and the identical server-built agency label at checkout confirmation and in all three order suburb columns, with no schema or customer/address persistence changes.
- IemsCountyAgency v1.6.0 adds an authorized agency delivery flag plus independent zero-cost **Pickup at IEMS Logistics** and **Delivery to Location** shipping modules. Pickup requires a signed-in customer with a current active, internally consistent county/agency affiliation; delivery additionally requires the active agency's flag. Both methods re-query current database state during status and quote processing, fail closed on schema/data mismatches, and remain independent of unit versus agency-fallback checkout selection.
- IemsCountyAgency v1.7.0 adds all-or-none managed unit delivery addresses, selection-aware IEMS quotes, a no-JavaScript two-step standard shipping flow, fixed code-resolved Indiana/US destinations, configurable IEMS Logistics pickup details, and final in-memory order delivery overrides. Pickup supports confirmed real units and the zero-unit agency fallback; delivery requires a confirmed real active unit, agency enablement, and a complete current unit address.
- IemsCountyAgency v1.8.0 adds one required agency payment mode plus the offline **Invoice Billing to Agency** and **Indianapolis EMS Unit** modules. Only the globally enabled method matching the signed-in customer's current unique, consistent, active affiliation is offered, with live fail-closed revalidation through final order processing.
- IemsCountyAgency v1.9.0 extends the existing delivery module with retained agency shipping categories, nullable managed unit mileage, configurable global out-of-county rates, three category-specific delivery labels, and pickup-preferred initial selection that respects explicit eligible choices. Existing delivery eligibility, managed-address, and final-order guards remain required.
- IemsCountyAgency v1.9.1 relabels the checkout selector as **Ordering Unit** and simplifies only the initial standard shipping step by suppressing the premature no-shipping warning and order-comments section until a selection is confirmed. Normal warnings and comments return after confirmation; One-Page Checkout and later checkout behavior remain unchanged.
- Unit delivery now combines the customer's company and name with the selected unit's canonical suburb label and managed street/city/state/ZIP/country. Pickup continues to use its configured recipient and company.
- Customer-group derivation, product-group availability/minimum/maximum rules, legacy migration/backfill, anomaly reporting, and the final operator runbook remain future work.

### v1.6.0 deployment checks

- Back up the database and upgrade IemsCountyAgency through Plugin Manager; verify `iems_agencies.delivery_enabled` exists as `TINYINT(1) NOT NULL DEFAULT 0` and existing agency rows remain intact and disabled.
- Assign **Customers > IEMS Agencies** to the intended Admin Profiles and verify authorized delivery-setting changes are logged.
- Install and enable the underscore-free `iemspickup` and `iemsdelivery` modules independently under **Modules > Shipping**, set the desired sort orders, and confirm neither module exposes a configurable cost.

### v1.7.0 deployment checks

- Back up the database and upgrade IemsCountyAgency through Plugin Manager. Verify the three `iems_units.delivery_*` columns are nullable with lengths 128/128/64. The normalizer converts blanks to `NULL` and clears partial triples.
- Install and enable **Delivery to Location** (`iemsdelivery`) under **Modules > Shipping**.
- Under **Customers > IEMS Agencies**, keep the selected unit's parent agency active and set **Delivery Enabled**. Delivery authorization is agency-level, not a unit setting.
- Under **Customers > IEMS Units**, keep each delivery-ready real unit active and provide complete street, city, and postcode values. Inactive units retain their address but cannot quote delivery.
- Install and enable **Pickup at IEMS Logistics** (`iemspickup`), then configure its recipient/location, optional company, street, city, and postcode. Pickup is withheld until all required values and the Indiana/United States code lookup validate.
- Treat the explicit zero-unit agency fallback as pickup-only; it never qualifies for Delivery to Location.
- Verify all shipping prerequisites before rollout. If no eligible, fully configured method exists, checkout displays the unavailable state and cannot proceed.
- Exercise the two-step standard checkout without JavaScript: confirm a unit/fallback, observe the refresh and shipping methods, then change the selection and verify the prior method is cleared. Repeat the supported OPC, virtual, PayPal/payment-return, cancellation, and stale-state paths.
- Place one pickup and one delivery order. Verify only delivery fields use the IEMS destination, all three order suburb labels remain canonical, and customer, billing, affiliation, unit, and address-book data are unchanged.
- Test signed-out, unaffiliated, inactive, cross-county, pickup-only, and delivery-enabled customers in standard and One-Page Checkout.
- Change an agency's delivery flag while checkout is in progress and confirm the next quote refresh reflects the new state immediately.
- Confirm agencies with active units and agencies using the v1.5.5 fallback receive the same shipping eligibility when their affiliation and delivery flag are otherwise identical.

### v1.8.0 deployment checks

- Back up the database and upgrade IemsCountyAgency through Plugin Manager. Verify `iems_agencies.payment_mode` is `VARCHAR(16) NOT NULL DEFAULT 'invoice'` and all existing agencies default to **Invoice Billing to Agency** unless explicitly changed.
- Under **Customers > IEMS Agencies**, verify the single payment selector, list display, Admin Profiles access, and activity-log entries for changed modes.
- Install and enable **Invoice Billing to Agency** (`iemsinvoice`) and **Indianapolis EMS Unit** (`iemsunit`) under **Modules > Payment**. Set any desired order status and sort order.
- Disable or uninstall all other payment modules through **Modules > Payment**; leave stock module source files intact.
- Test one active agency in each mode, then test guest, unaffiliated, inactive county/agency, inconsistent affiliation, duplicate affiliation, malformed schema/data, invalid mode, and mode changes during checkout. No invalid state may expose or process either custom method.

### v1.9.0 deployment checks

- Back up the database and upgrade through Plugin Manager. Confirm `iems_agencies.shipping_category` is exactly `ENUM('marion','iems','out_of_county') NOT NULL DEFAULT 'marion'` and `iems_units.one_way_miles` is `DECIMAL(7,2) NULL DEFAULT NULL`.
- Existing county codes need no rewrite: `49` and `049` both initialize agencies to `marion`; other valid counties initialize to `out_of_county`. Shipping comparisons normalize one to three ASCII digits representing `1` through `92` to three digits in memory. Blank/malformed, zero, out-of-range, and overlength codes are rejected. Installer preflight stops before schema/data changes if any agency's joined county is missing or invalid. Repair genuine data errors through authorized database maintenance; preserve valid historical codes and leave the foundation import unchanged. Counties remain application read-only. Explicitly review categories after upgrade and assign `iems` where needed; subsequent upgrades/reinstall never overwrite retained categories.
- County validation runs before any checkout unit/fallback selection: invalid county codes disable pickup as well as delivery. Delivery-only schema/category/mileage failures instead leave pickup eligible when county/affiliation/selection remain valid. Verify `49` and `049` both work without changes to stored codes or selection labels. Generated category/mileage columns must fail schema validation.
- Malformed existing new-column definitions stop install/upgrade without coercing retained values. Repair from a verified backup with deliberate schema/data review, then retry. MySQL DDL is not transactional: after an interrupted/failed migration, inspect actual schema and category data before retrying rather than assuming automatic rollback.
- Use **Customers > IEMS Agencies** to manage categories and the existing independent delivery flag. Use **Customers > IEMS Units** for verified one-way mileage: blank is unknown, `0` is valid, maximum is `99999.99`, and at most two fractional digits are accepted. Keep real units active with complete delivery addresses.
- Retain the same `iemsdelivery` module. Verify **Indianapolis EMS / Eskenazi Delivery** and **Marion County Agency Delivery** are free, while **Out-of-County Agency Delivery** uses the globally configured rates and current unit mileage.
- Review `MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE` (`35.00`) and `MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE` (`0.55`) under **Modules > Shipping**. Upgrade only adds missing keys for installed delivery, including disabled installations; customized values, status, and sort order are preserved. Native module install/remove continues to manage those settings.
- Rates must be canonical decimals from `0` through `99999.99`, with at most two fractional digits, no whitespace, and no exponent notation. Out-of-county cost is the flat rate plus **one-way** mileage multiplied by the per-mile rate, never doubled distance. At default rates, 10 one-way miles costs `40.50`. Every quote reads database rates without constant/default fallback; calculation uses integer hundredths and final half-up rounding to cents. **IEMS Agency Delivery** is the generic header, not a replacement for the three exact method labels.
- Verify pickup is initially preferred only when eligible, explicit eligible delivery choices survive refresh, agency fallback remains pickup-only, and missing/invalid category, mileage, rate, address, or schema prevents inappropriate delivery. Recheck these boundaries after admin changes during checkout and immediately before order insertion.
- Change module display sort order and confirm available pickup remains preferred through `NOTIFY_SHIPPING_MODULE_CALCULATE_CHEAPEST`. Missing/invalid new delivery schema must leave otherwise eligible pickup usable. Change category or rates after the order object is built: final validation must compare both session shipping and order label/cost with the new quote and return to shipping on a mismatch.
- Uninstall/reinstall preserves categories, mileage, addresses, flags, payment modes, and reference rows. Uninstall removes shipping/payment module settings; reinstall does not restore those settings or rerun county-based classification. Back up settings separately if they will be reused.
- Run v1.9 installer/admin/shipping/checkout/payment harnesses and PHP lint, then stage a real MySQL/MariaDB upgrade against a database copy. The installer harness uses a fake database and does not replace this migration rehearsal. Test standard and One-Page Checkout, zero/unknown mileage, custom rate preservation, repeat upgrades, and no customer/address-book writes.

### v1.9.1 deployment checks

- Upgrade through Plugin Manager and confirm the standard checkout shipping page initially shows the delivery address, required **Ordering Unit** selector, validation messages, and continue control without the stock no-shipping warning or order-comments textarea.
- Confirm a valid unit or agency fallback without JavaScript. Verify shipping choices appear, comments return, and a genuine zero-method state displays the normal warning and blocks checkout as before.
- Verify the **Ordering Unit** heading in the supported standard, virtual-order, and One-Page Checkout paths; leave Admin **IEMS Units** terminology unchanged.
- Verify final unit delivery shows the customer's company/name, selected unit suburb label, and managed unit street/city/Indiana/ZIP/United States; verify pickup still shows its configured recipient/company.
- Run all v1.9.1 harnesses, the v1.9.0 checkout regression harness, and PHP lint.

The branch numbering below is retained as the original plan. Delivery was subsequently organized into incremental plugin versions, so this status section is authoritative for completed scope.

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
