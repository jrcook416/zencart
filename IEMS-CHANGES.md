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
- Packages two independent Modules > Shipping integrations: **Pickup at IEMS Logistics** for every signed-in customer with a valid active county/agency affiliation, and **Delivery to Location** only when that affiliated agency also has delivery enabled.
- Both methods are always exactly zero cost. Their module settings expose only normal enable/disable and sort order controls; configuration cannot introduce a charge.
- Revalidates schema, authentication, affiliation, active county/agency state, county-to-agency consistency, and the current agency flag during status and quote processing. Guest, unaffiliated, inactive, inconsistent, missing-field, duplicate-row, and malformed database states fail closed without transient eligibility caching.
- Shipping eligibility is independent of v1.5.5 checkout unit selection and agency fallback state. Standard and One-Page Checkout use the existing shared shipping-module discovery pipeline with no core or checkout-template changes.
- Plugin Manager install/upgrade adds the field if missing without modifying existing IEMS data. Uninstall intentionally retains all IEMS tables, rows, and the delivery flag.
- Validation includes the v1.6.0 agency, unit, checkout, and shipping harnesses; unchanged v1.5.5, v1.5.0, and v1.4.0 harnesses; PHP lint; prior-version/core/template diff checks; and independent review.

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
