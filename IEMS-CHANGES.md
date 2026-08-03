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
