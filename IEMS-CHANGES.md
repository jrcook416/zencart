# IEMS-CHANGES

This document tracks custom divergences in this fork from upstream `zencart/zencart` **2.2** (as of the **v2.2.2** release line), for maintainer onboarding and future merge/backport work.

## Plugins added (fork-specific, not upstream baseline)

The fork history includes imported/bundled plugin work beyond upstream `zencart/zencart` 2.2:

- **DbIo** (`wombat/dbio`) — imported in commit `d731857df`.
- **Edit Orders** — imported in commit `395e35ca5`.
- **One-Page Checkout** — imported in commit `e77e2bece`.
- **Priority Handling** — imported in commit `8acdbc3ed`.
- **Postcode Auto Fill** — imported in commit `0a709bddc`.
- **ZCA Bootstrap Template v3.8.0** — imported in commit `975ffeafd` (see `includes/templates/bootstrap/template_info.php`).

## IEMS template

- Imported IEMS template files from the `v300` branch (`5e35fd685`).
- Primary template path: `includes/templates/iems/`.

## IEMS County Agency plugin v1.4.0

- Adds **Customers > IEMS Units** for listing, searching, filtering, creating, editing, deactivating, and reactivating units. Unit identifiers are uppercase alphanumeric (1-10 characters), names are 1-128 clean UTF-8 characters, and identifiers are unique within an agency.
- A unit's county is always derived from its selected agency. Agency reassignment updates `agency_ID` and `county_ID` together; counties have no application mutation surface and remain back-end-database maintained only.
- Unit status changes are CSRF-protected POST operations. Deactivation retains existing references while excluding the unit from future active-only selectors.
- Access uses native Admin Profiles. A superuser assigns **Customers > IEMS Units** under **Admin Access > Profiles**; unassigned direct GET and POST requests are denied, while superusers retain native access.
- Upgrade from v1.3.0 through Plugin Manager. Registration is idempotent and does not remove existing agency-page profile assignments or recreate/drop IEMS data. Uninstall removes plugin navigation registrations but intentionally retains IEMS data and configuration.
- Deployment validation: back up the database; confirm the `iems_units` unique and foreign-key constraints; upgrade the plugin; assign the units page to the intended profiles; test authorized, denied, and superuser access; test create/edit validation and agency reassignment; test deactivate/reactivate; confirm activity-log entries; and verify prior agency, affiliation, registration, account-edit, and lock behavior.

## IEMS County Agency plugin v1.5.0

- Requires each signed-in, affiliated customer to select an active unit for the current order. Choices are restricted to units matching the customer's active county and agency hierarchy.
- Supports standard multi-page checkout, One-Page Checkout, virtual-order payment flow, and the template-default, responsive fallback, Bootstrap, and IEMS rendering paths.
- Revalidates authentication, affiliation, hierarchy, unit status, cart identity, and label length before order creation. Client-supplied labels are never trusted; the label is rebuilt from current database values as `<county number> <agency identifier> <unit identifier> <unit name>`.
- Keeps the unit selection transient and order-specific. It is bound to the current customer and cart, survives legitimate checkout and payment redirects, and is cleared on order completion, cart restart, logout, or payment cancellation.
- Writes the rebuilt label identically to `orders.customers_suburb`, `orders.delivery_suburb`, and `orders.billing_suburb` through the pre-insert order object. It does not update customers, address-book entries, affiliations, configuration, cookies, or a separate history table.
- Upgrade through Plugin Manager from v1.4.0. No schema or configuration changes are required.
- Live validation checklist: test standard and One-Page Checkout with physical and virtual carts; verify missing, stale, inactive, and cross-agency units block checkout; verify payment cancellation/return behavior; confirm all three order suburb columns contain the same label; confirm customer, address-book, and affiliation data remain unchanged; and rerun agency/unit administration and profile-access checks.

## IEMS County Agency plugin v1.5.5

- Preserves v1.5.0 behavior when the signed-in customer's valid active agency has one or more active units: checkout lists only active units and requires an explicit unit selection.
- When that agency has zero active units, checkout lists exactly one explicit agency fallback as `<county number> <agency identifier> <agency name>`. The fallback is never auto-selected.
- Uses a fixed nonnumeric fallback token recognized only by the server. The agency reference and label are rebuilt from the customer's current active county/agency affiliation; submitted labels and agency IDs are not trusted.
- Revalidates the active, internally consistent county/agency hierarchy and the current active-unit set whenever transient state is restored. Adding or reactivating any unit invalidates an agency fallback before order creation and requires an explicit unit selection.
- Stores transient selection state as customer ID, cart ID, selection type, server-derived reference ID, and checkout flow. Standard, One-Page Checkout, virtual-order, PayPal, cancellation, cart restart, logout, and order-completion behavior remain covered by the v1.5.0 lifecycle hooks.
- Rejects empty and greater-than-128-character unit or agency labels without truncation. The rebuilt label shown at confirmation is assigned identically to `orders.customers_suburb`, `orders.delivery_suburb`, and `orders.billing_suburb`.
- Makes no schema or configuration changes and writes no customer, address-book, affiliation, cookie, unit, fallback-row, or separate history data. Existing checkout template insertions are reused unchanged.
- Upgrade through Plugin Manager from v1.5.0. Validation includes the v1.5.5 agency, unit, and checkout harnesses; unchanged v1.5.0 and v1.4.0 harnesses; PHP lint; prior-version/core/template diff checks; and independent review.

## IEMS County Agency plugin v1.6.0

- Adds `iems_agencies.delivery_enabled` as an idempotent, non-null boolean field that defaults to disabled for every existing and newly-created agency unless an authorized administrator enables it.
- Extends **Customers > IEMS Agencies** with validated delivery enablement management, list display, persistence, and admin activity logging. Inactive counties or agencies remain ineligible regardless of the flag.
- Packages two independent Modules > Shipping integrations: **Pickup at IEMS Logistics** (`iemspickup`) for every signed-in customer with a valid active county/agency affiliation, and **Delivery to Location** (`iemsdelivery`) only when that affiliated agency also has delivery enabled. The underscore-free module codes preserve Zen Cart's required `{module}_{method}` shipping-selection parsing.
- Both methods are always exactly zero cost. Their module settings expose only normal enable/disable and sort order controls; configuration cannot introduce a charge.
- Revalidates schema, authentication, affiliation, active county/agency state, county-to-agency consistency, and the current agency flag during status and quote processing. Guest, unaffiliated, inactive, inconsistent, missing-field, duplicate-row, and malformed database states fail closed without transient eligibility caching.
- Shipping eligibility is independent of v1.5.5 checkout unit selection and agency fallback state. Standard and One-Page Checkout use the existing shared shipping-module discovery pipeline with no core or checkout-template changes.
- Plugin Manager install/upgrade adds the field if missing without modifying existing IEMS data. Uninstall intentionally retains all IEMS tables, rows, and the delivery flag.
- Validation includes the v1.6.0 agency, unit, checkout, and shipping harnesses; unchanged v1.5.5, v1.5.0, and v1.4.0 harnesses; PHP lint; prior-version/core/template diff checks; and independent review.

## IEMS County Agency plugin v1.7.0

- Adds nullable `delivery_street_address` (128), `delivery_city` (128), and `delivery_postcode` (64) fields to `iems_units`. Plugin Manager install/upgrade idempotently adds or normalizes malformed definitions, trims blank values to `NULL`, and clears partial legacy address triples so a unit is always either delivery-ready or delivery-unavailable.
- Extends **Customers > IEMS Units** with managed street, city, and postcode fields, fixed Indiana/United States guidance, all-or-none server validation, escaped list/form output, an address-ready indicator, and readiness-only activity logging. Clearing all three fields disables delivery without deleting the unit or its retained address history semantics; inactive units retain data but remain ineligible.
- Changes standard checkout shipping to a deterministic two-step flow. The first submit confirms a current active unit or the explicit zero-unit agency fallback and refreshes the page; shipping methods display only after that cart/customer-bound selection validates. Changing the selection clears the selected shipping method and forces fresh quotes. The same validation remains enforced across One-Page Checkout, virtual carts, payment returns, PayPal boundaries, cancellation, logout, and final order creation.
- Makes **Delivery to Location** require the agency delivery flag, a confirmed real active unit (never the agency fallback), and a complete current managed unit address. **Pickup at IEMS Logistics** remains available for confirmed active-unit and zero-unit agency-fallback selections.
- Adds pickup-module configuration for recipient/location name (default `IEMS Logistics`), optional company, street address, city, and postcode. Indiana and United States are resolved from current country/zone rows by stable `US`/`IN` codes. Pickup is visibly unavailable when required settings, lengths, characters, or the fixed region lookup are invalid; cost remains hardcoded numeric zero.
- Overrides only the in-memory order delivery address for `iemsdelivery` or `iemspickup`. Unit delivery uses `<unit identifier> <unit name>` as delivery name and the agency name as company; pickup uses module configuration. Both retain the canonical IEMS unit/fallback label in `delivery_suburb`, continue the existing `customers_suburb` and `billing_suburb` behavior, and never update customer or address-book rows.
- Revalidates selection, affiliation, agency flag, unit status/address, pickup configuration, and Indiana/US lookup at quote and final order boundaries. Stale, partial, malformed, overlength, duplicate, missing-schema, inactive, or changed state fails explicitly before insertion.
- **Deployment/operations checklist:** upgrade through Plugin Manager from v1.6.0; install and enable `iemsdelivery`; enable Delivery on the selected unit's active parent agency under **Customers > IEMS Agencies**; keep the selected real unit active with complete street, city, and postcode under **Customers > IEMS Units**; and install/enable `iemspickup` with complete recipient, street, city, and postcode settings. Agency-fallback selections are pickup-only. Checkout cannot proceed when no eligible, fully configured method exists, so verify these settings before rollout. Validation includes all v1.7.0 deterministic harnesses, unchanged v1.4.0-v1.6.0 harnesses, PHP lint, prior-version and template guards, and security/compatibility review.

## IEMS County Agency plugin v1.8.0

- Adds `iems_agencies.payment_mode` as a non-null `varchar(16)` with the supported values `invoice` and `iems_unit`. Existing, missing, null, or invalid values normalize to `invoice`; newly created agencies also default to `invoice`.
- Extends **Customers > IEMS Agencies** with one required payment-method selector, list display, strict server validation, persistence, and old-to-new activity logging. The options are exactly **Invoice Billing to Agency** and **Indianapolis EMS Unit**, so an agency cannot select both or neither.
- Packages matching independent offline payment modules: `iemsinvoice` and `iemsunit`. Each retains normal **Modules > Payment** installation, enable/disable, order-status, and sort-order controls and adds no checkout fields or external processing.
- At checkout, only the globally enabled module matching the signed-in customer's one current valid active agency is offered. Eligibility revalidates the required schema, unique affiliation, active county and agency, consistent hierarchy, positive identifiers, and stored mode during module discovery, confirmation, and final order processing.
- Guest, unaffiliated, inactive, inconsistent, duplicate, malformed, missing-schema, and invalid-mode states fail closed. A mode changed after selection clears the stale payment choice and returns the customer to payment selection before an order is inserted.
- Plugin uninstall removes configuration for either installed custom payment module while intentionally retaining all agency rows and `payment_mode` values.
- **Deployment/operations checklist:** back up the database; upgrade through Plugin Manager from v1.7.0; verify every agency initially shows **Invoice Billing to Agency**; explicitly change only agencies requiring **Indianapolis EMS Unit**; install and enable both custom modules under **Modules > Payment**; then disable or uninstall every other payment module through normal administration. Do not remove stock module source files.

## IEMS County Agency plugin v1.9.0

- Adds `iems_agencies.shipping_category` as `ENUM('marion','iems','out_of_county') NOT NULL DEFAULT 'marion'`. Only when adding the column, existing agencies whose joined county has `county_number` `49` or `049` receive `marion`; every other valid county's agency receives `out_of_county`. Repeated upgrades and reinstall never infer or overwrite an existing category, including explicit `iems` choices.
- Adds `iems_units.one_way_miles` as `DECIMAL(7,2) NULL DEFAULT NULL`. Blank means unknown (`NULL`); zero is valid. Authorized unit administrators accept only nonnegative mileage through `99999.99` with no more than two decimal places. Mileage and complete managed addresses remain separate requirements.
- Extends agency administration with shipping-category management and unit administration with mileage management. Administrative category changes must follow the current county/category rules; migration does not infer IEMS identity from agency names or payment mode.
- Extends the existing `iemsdelivery` module rather than introducing replacement shipping codes. Its exact customer-facing labels are **Indianapolis EMS / Eskenazi Delivery** (zero cost), **Marion County Agency Delivery** (zero cost), and **Out-of-County Agency Delivery** (calculated cost). Pickup remains zero cost and is the preferred initial eligible method; a customer's explicit eligible shipping choice remains authoritative.
- Adds global `MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE` (default `35.00`) and `MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE` (default `0.55`) under **Modules > Shipping**. Plugin install/upgrade backfills only missing rate keys when `iemsdelivery` is already installed, including disabled installations. It preserves status, sort order, existing rates, and other settings; it does not install the module on the operator's behalf. Native module install/remove continues to own configuration.
- Delivery uses the generic module header **IEMS Agency Delivery** and the category-specific method labels above. Rates are reread from the database for every quote, with no constant/default fallback. Each rate must be a canonical decimal from `0` through `99999.99`, with at most two fractional digits and no whitespace or exponents. Out-of-county cost is the flat rate plus the selected unit's **one-way** mileage multiplied by the per-mile rate; mileage is not doubled. Pricing uses integer hundredths for rates/mileage and final half-up rounding to cents.
- Preserves agency delivery enablement, active affiliation/hierarchy checks, real-unit selection, complete managed destination, fixed Indiana/US lookup, and quote/final-order guards. Agency fallback stays pickup-only. Missing or malformed schema, unsupported categories, and invalid required mileage/rates fail closed rather than yielding free delivery.
- Missing or invalid new delivery schema does not by itself disable otherwise eligible pickup. Pickup preference uses `NOTIFY_SHIPPING_MODULE_CALCULATE_CHEAPEST` independently of display sort order and never overwrites an explicit eligible choice. Final order validation compares the current category label and price with both session shipping and the already-built order; any mismatch returns to shipping for reselection.
- Plugin uninstall retains categories, mileage, addresses, agency flags, payment modes, and every IEMS data row, while native shipping/payment module removal clears module configuration. Malformed existing v1.9 column definitions stop installation/upgrade before schema/data mutations; repair them deliberately rather than coercing retained values.
- **County-code compatibility:** historical foundation codes `01` through `92` remain unchanged. Shipping comparisons accept one to three ASCII digits with numeric values `1` through `92`, normalize in memory to three digits, and treat `49` and `049` identically as Marion (`049`). Blank, malformed, zero, out-of-range, and overlength codes fail closed. Installer preflight aborts before schema/data mutation if any agency has a missing county or invalid joined county code. No county-code database rewrite, name/agency-identifier inference, historical SQL edit, or earlier-version change is required.
- Checkout validates county codes before accepting any unit or agency fallback, so an invalid county code blocks both pickup and delivery. This differs from malformed delivery-only schema/category/mileage, which blocks delivery but leaves pickup eligible when county/affiliation/selection are otherwise valid. Normalized comparisons never rewrite stored county labels. Generated category/mileage columns are rejected by schema guards.
- **Validation:** run the v1.9 installer, agency, unit, checkout, shipping, and payment harnesses and PHP lint. The installer harness covers fresh installation, missing-column upgrades, repeated upgrade/reinstall, compatible county-code widths, invalid county preflight, malformed new-column rejection, missing-only rate backfill, settings preservation, and retained schema/data after uninstall. It uses a deterministic fake database, not a live MySQL/MariaDB migration; stage a real database backup/upgrade test before deployment.

## IEMS County Agency plugin v1.9.1

- Relabels the customer-facing checkout selector from **IEMS Unit** to **Ordering Unit** across standard checkout, One-Page Checkout, virtual-order payment, and supported template paths through the shared plugin language key. Administrative **IEMS Units** terminology is unchanged.
- On the initial standard checkout shipping step, before a unit or agency fallback has been confirmed, displays only the normal delivery-information/address block, required Ordering Unit selector, validation messages, and continue controls. The stock no-shipping warning and the complete order-comments section are suppressed in this state.
- After a valid selection is confirmed, restores normal shipping choices, the genuine no-method warning when applicable, and order comments. The guard uses a shipping-page-only plugin state flag and defaults to normal template behavior when the plugin is absent.
- Preserves the no-JavaScript confirmation round trip, One-Page Checkout behavior, shipping/payment validation, later checkout comments, and fail-closed final-order checks. No schema, configuration, admin terminology, or historical plugin version changes are introduced.
- **Validation:** run all v1.9.1 deterministic harnesses, the v1.9.0 checkout regression harness, and PHP lint for v1.9.1 plus the three guarded checkout templates.

## IEMS County Agency plugin v1.9.2

- For unit delivery, preserves the customer's company, first name, and last name while replacing the destination fields with the selected unit's canonical suburb label, managed street, city, fixed Indiana state, postcode, and United States country. Pickup continues to use its configured recipient and company.
- Preserves all v1.9.1 initial checkout presentation, selector labeling, shipping/payment validation, and fail-closed behavior without schema or configuration changes.
- **Validation:** run all v1.9.2 deterministic harnesses, the affected v1.9.1 checkout and shipping regression harnesses, and PHP lint for the v1.9.2 payload.

## Database changes

- Added `is_guest_order` column to the `orders` table with an **add-if-missing guard**.
  - Test seeder path: `not_for_release/testFramework/Support/Database/Seeders/InitialSetupSeeder.php`.
  - Runtime upgrade path: `wombat/includes/init_includes/init_checkout_one_upgrade.php`.

## Seeder changes

- `InitialSetupSeeder` was refactored to remove admin setup responsibilities (`8da1b2d40`).
- Additional seeder iterations were applied for template settings and broader configuration updates (for example `c1da5e252`), rather than remaining static from upstream defaults.

## Bug fixes

- Fixed `buy_now` referer host matching for CI/CDN/proxy environments by using `X-Forwarded-Host` (comma-list safe, port-stripped, IPv6-safe) instead of bare `HTTP_HOST` comparison in `includes/application_top.php` (`9b454992e`).
- Fixed SEK checkout success selector fallback assertions (`74d1a6ac5`).
- Backported plugin PSR-4 autoloading support from upstream/2.2 into `includes/application_top.php` (plugin namespace prefix registration and optional plugin-root `psr4Autoload.php` loading; see `392b2b913`, `5366e6f7f`).

## Test infrastructure

- Isolated `LowOrderFeeTest` to prevent cross-test configuration bleed (`ddd29d73a`).
- Made `setUp`/`tearDown` public in `LowOrderFeeTest` (`b56db83ea`).
- Synced feature-test workflows and support files with upstream 2.2 via upstream merge/backport activity (for example `e2b325505` and related upstream/2.2 sync commits).
