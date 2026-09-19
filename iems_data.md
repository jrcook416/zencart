# IEMS Data Definitions

Reference documentation for the IEMS `county -> agency -> unit` data model
introduced in the `iems-county-agency-unit-foundation` branch. The dated
`zc_install/sql/iems_custom/iems_foundation.sql` file preserves that historical
foundation/import; subsequent runtime schema changes are owned by each plugin
version's idempotent installer.

## 1) Tables and fields

### `iems_counties`
| Field | Type/Role |
|---|---|
| `county_ID` | Auto-number primary key |
| `county_number` | Indiana state county code. Historical imports use two digits (`01`, `03`, `49`). Shipping comparisons accept one to three ASCII digits, numeric values `1` through `92`, and normalize in memory to three digits: `49` and `049` both identify Marion (`049`). Stored codes are not rewritten. |
| `county_name` | Indiana county name |

### `iems_agencies`
| Field | Type/Role |
|---|---|
| `agency_ID` | Auto-number primary key |
| `county_ID` | Required foreign key -> `iems_counties.county_ID` |
| `agency_identifier` | Alphanumeric, max 10 characters |
| `agency_name` | Clean display name |
| `delivery_enabled` | Non-null boolean controlled by authorized agency administrators; defaults to `0` (disabled), including on upgrade for all existing agencies |
| `payment_mode` | Required agency payment mode: `invoice` or `iems_unit`; defaults to `invoice` for existing and newly-created agencies |
| `shipping_category` | `ENUM('marion','iems','out_of_county') NOT NULL DEFAULT 'marion'`; authorized agency administrators manage the current category |

### `iems_units`
| Field | Type/Role |
|---|---|
| `unit_ID` | Auto-number primary key (explicit PK per repo convention) |
| `county_ID` | Foreign key -> `iems_counties.county_ID` |
| `agency_ID` | Foreign key -> `iems_agencies.agency_ID` |
| `unit_identifier` | Alphanumeric, max 10 characters |
| `unit_name` | Clean display name |
| `delivery_street_address` | Nullable managed delivery street, max 128 characters |
| `delivery_city` | Nullable managed delivery city, max 128 characters |
| `delivery_postcode` | Nullable managed delivery postcode, max 64 characters |
| `one_way_miles` | `DECIMAL(7,2) NULL DEFAULT NULL`; unknown mileage is `NULL`, while zero is valid; supported input range is `0` through `99999.99` with at most two decimal places |

Each table also carries a `status` flag (active/inactive) and `date_added` /
`last_modified` timestamps, per the retention model in section 4.

### Units import status (Marion County legacy data)

141 of 146 legacy unit records have been imported. The remaining 5 are
intentional non-imports from source cleanup: one UI placeholder row, one
exact duplicate MD019 row, and three superseded "(OLD)" MD097/MD098/MD099
rows. Previously deferred no-code rows (EHS clinic locations, IEMS
functional/location rows, and self-referential agency-name rows) have now
been assigned explicit `unit_identifier` values and imported; see
reconciliation notes and `iems_import_log` in `iems_foundation.sql`.

## 2) Constraints

- `county_number` is unique globally.
- `agency_identifier` is unique per county (`county_ID` + `agency_identifier`).
- `unit_identifier` is unique per agency (`agency_ID` + `unit_identifier`).
- Foreign key delete behavior: `RESTRICT`.
- Foreign key update behavior: `ON UPDATE CASCADE`.
- `agency_identifier` and `unit_identifier` must be uppercase alphanumeric only.

## 3) Selection/display contract

| Selector | Value | Label format | Example |
|---|---|---|---|
| County | `county_ID` | `<county_number> <county_name>` | `049 Marion` |
| Agency | `agency_ID` | `<county_number> <agency_identifier> <agency_name>` | `049 IEMS Indianapolis EMS` |
| Unit | `unit_ID` | `<county_number> <agency_identifier> <unit_identifier> <unit_name>` | `049 IEMS MED1 Medic 1` |

Dependency flow: **county -> agency -> unit** (agency options are filtered by
selected county; unit options are filtered by selected agency).

## 4) Governance decisions

- **System-of-record owner:** IEMS superusers/admin ops.
- **Unit address owner:** Authorized IEMS unit administrators maintain delivery street, city, and postcode through **Customers > IEMS Units**. The three values are all blank or all nonblank. State and country are not stored per unit; runtime resolves Indiana and United States by stable `IN`/`US` codes.
- **Shipping eligibility owner:** Agency administrators maintain `delivery_enabled` through **Customers > IEMS Agencies**. Pickup requires a confirmed current unit or explicit zero-unit agency fallback. Delivery additionally requires this flag, a real active unit selection, and that unit's complete managed address.
- **Fail-closed shipping:** Guest, unaffiliated, inactive, cross-county/inconsistent, duplicate, malformed, stale, partial-address, missing-schema, or invalid fixed-region states do not expose an invalid IEMS method. An inactive county, agency, or unit is never made eligible by retained data or `delivery_enabled`.
- **Pickup destination:** The `iemspickup` module owns recipient/location name, optional company, street, city, and postcode configuration. Required fields must be complete and fit order-column lengths. Indiana/United States remain fixed and code-resolved.
- **Shipping category owner:** Authorized agency administrators maintain `shipping_category`. Category is independent of `payment_mode` and does not replace `delivery_enabled`. County/category transitions must satisfy agency-admin validation.
- **Mileage owner:** Authorized unit administrators maintain `one_way_miles`. Blank clears to `NULL`; negative, overrange, exponent, and more-than-two-decimal inputs are rejected. Existing units begin with unknown mileage; do not replace unknown mileage with zero merely to enable delivery.
- **Shipping cost (v1.9.0):** Pickup remains free. The existing `iemsdelivery` module emits **Indianapolis EMS / Eskenazi Delivery** for `iems` and **Marion County Agency Delivery** for `marion`, both free. `out_of_county` emits **Out-of-County Agency Delivery**, charging the global flat rate plus the unit's **one-way** mileage multiplied by the global per-mile rate. Mileage is not doubled. At the default rates, 10 one-way miles costs `40.50`. Missing or invalid required pricing inputs do not produce a delivery quote.
- **Global rates:** `MODULE_SHIPPING_IEMSDELIVERY_FLAT_RATE` defaults to `35.00`; `MODULE_SHIPPING_IEMSDELIVERY_PER_MILE_RATE` defaults to `0.55`. Manage these under **Modules > Shipping**, not per agency or unit. Each value must be a canonical decimal from `0` through `99999.99`, with at most two decimal places and no whitespace or exponents. Every quote rereads database settings without falling back to constants or defaults. Computation uses integer hundredths for rates/mileage and final half-up rounding to cents.
- **Default selection:** Prefer eligible **Pickup at IEMS Logistics** on initial shipping selection via `NOTIFY_SHIPPING_MODULE_CALCULATE_CHEAPEST`, independently of display sort order. Preserve an explicit eligible customer choice rather than replacing it with pickup on refresh. Missing/invalid new delivery schema disables delivery, not otherwise eligible pickup.
- **Quote/order consistency:** The delivery module header is **IEMS Agency Delivery**, while method labels remain category-specific. Final order guards compare the current category label and calculated price against both session shipping and the already-built order information. A mismatch returns to shipping for reselection rather than silently charging a changed price.
- **Payment eligibility owner:** Agency administrators select exactly one mode under **Customers > IEMS Agencies**. `invoice` maps to **Invoice Billing to Agency** and `iems_unit` maps to **Indianapolis EMS Unit**.
- **Fail-closed payment:** Only the globally enabled payment module matching the signed-in customer's current unique, internally consistent, active county/agency affiliation is offered. Guest, unaffiliated, inactive, duplicate, malformed, missing-schema, inconsistent, and invalid-mode states expose neither custom method.
- **Payment processing:** Both methods are offline labels with no extra checkout fields and no external processor. Zen Cart records the selected module normally on the order. Eligibility is checked again at confirmation and immediately before order processing.
- **Live evaluation:** Selection is transient and bound to the current customer/cart. Quote and final order processing re-query current affiliation, agency, unit, address, configuration, and region state. Changing the unit/fallback invalidates the shipping method and recomputes quotes.
- **Order persistence:** The selected IEMS method overrides only the in-memory order delivery array before insertion. It never copies managed addresses to customers or address books. `delivery_suburb` retains the canonical IEMS unit/fallback label, alongside the established customer/billing suburb behavior.
- **Updates:** applied via repo SQL imports run by authorized admins.
- **Update cadence:** ad hoc (as new counties/agencies/units are identified).
- **Retention:** active/inactive model; hard deletes are avoided.
- **Inactive behavior:** existing customer/order/address assignments
  referencing an inactive county, agency, or unit remain valid; inactive
  entries are blocked from appearing in new selection dropdowns.
- **Import audit trail:** required for every bulk import — records who
  performed the import, when, and the source file/version (see
  `iems_import_log` table).
- **Seed scope:** import all known data at the time of each import.
- **Plugin lifecycle:** IemsCountyAgency v1.9.0 retains prior shipping/address/payment fields and adds the category/mileage fields if absent. It only backfills missing delivery-rate configuration for an already installed delivery module, even when disabled. Uninstall removes custom shipping/payment module configuration through native module removal while retaining every IEMS reference field and row, including category and mileage. Reinstall does not rerun category inference on retained schema.
- **One-time category migration:** When `shipping_category` is first added, joined `iems_counties.county_number` values `49` and `049` become `marion`; all other valid counties' agencies become `out_of_county`. No agency automatically becomes `iems`. This does not run when the column already exists, so later administrator choices survive every upgrade.
- **County-code compatibility:** The foundation SQL's two-digit codes remain valid without database rewriting. Admin/runtime comparisons normalize one to three ASCII digits, numeric values `1` through `92`, to three digits in memory. Both `49` and `049` therefore identify Marion. Blank, non-ASCII/malformed, zero (`000`), overrange, and more-than-three-digit values are rejected. Installer preflight aborts before changing schema/data if any agency's joined county is missing or invalid. Correct genuinely invalid data through authorized database maintenance; do not rename valid historical county codes. Counties remain application read-only.
- **County validation and pickup:** Checkout validates the county code before accepting any unit or agency fallback. Invalid county codes therefore disable both pickup and delivery. In contrast, malformed delivery-only schema/category/mileage disables delivery while pickup remains available for an otherwise valid county, affiliation, and confirmed selection. In-memory county normalization does not alter stored codes or their displayed/persisted selection labels.
- **Malformed schema:** An existing category or mileage column with the wrong field name, type, enum members/order, nullability, default, or generated-column metadata stops install/upgrade with a Plugin Manager error. Retained values are not coerced. Back up and repair the definition/data intentionally before retrying. Runtime delivery readers also reject these definitions until valid schema and data are restored.

## 5) Shipping deployment/operations checklist

- Back up the database and upgrade to v1.9.0 through Plugin Manager. Valid historical county codes need no rewrite: `49` and `049` both migrate as Marion. Resolve any invalid county relationship/code reported by preflight before retrying. Confirm exact category/mileage definitions and review all migrated categories.
- Install and enable the existing `iemsdelivery` module under **Modules > Shipping**; do not create a separate module per category. Review the global flat/per-mile rates (defaults `35.00` / `0.55`). Existing installations receive only missing rate settings.
- Under **Customers > IEMS Agencies**, verify the shipping category for every agency, explicitly selecting `iems` where appropriate. Do not use a payment-mode change to assign shipping category.
- Under **Customers > IEMS Agencies**, keep the selected unit's parent agency active and set **Delivery Enabled**. Delivery authorization is agency-level, not a unit setting.
- Under **Customers > IEMS Units**, keep the selected real unit active and provide complete street, city, and postcode values. For out-of-county delivery, record verified one-way mileage within `0`–`99999.99`; blank remains unknown and blocks that delivery quote.
- Install and enable **Pickup at IEMS Logistics** (`iemspickup`), then configure its recipient/location, street, city, and postcode. Pickup is withheld if any required value is missing.
- Treat the explicit zero-unit agency fallback as pickup-only; it never qualifies for any category of delivery.
- Verify these prerequisites before rollout. If no eligible, fully configured shipping method exists, checkout displays the unavailable state and cannot proceed.

## 6) Payment deployment/operations checklist

- Install and enable **Invoice Billing to Agency** (`iemsinvoice`) and **Indianapolis EMS Unit** (`iemsunit`) under **Modules > Payment**.
- Disable or uninstall every other payment module through normal administration controls; leave stock module source files intact.
- Verify each agency's one selected payment mode under **Customers > IEMS Agencies**. Existing and newly-created agencies default to invoice billing.
- Place an order for each mode and verify the selected label is recorded through normal Zen Cart payment behavior, without additional payment fields or external processing.

## 7) Branch acceptance criterion

- Standard GitHub Actions testing (existing suites: `zc_unit_test_suite.yml`,
  `zc_feature_test_admin_suite.yml`, `zc_feature_test_store_suite.yml`) must
  continue to pass; no new CI configuration is introduced by this branch.
- Run the v1.9 deterministic harnesses (`php zc_plugins/IemsCountyAgency/v1.9.0/tests/installer_harness.php`, plus agency, unit, checkout, shipping, and payment harnesses) and lint the changed PHP files. Installer coverage includes fresh foundation-schema installation, compatible county-code widths and invalid-code preflight, upgrades/repeated upgrades, retained category choices and mileage endpoints, malformed schema, installed/disabled/uninstalled delivery, missing-only rate backfill, and uninstall/reinstall.
- The installer harness models SQL effects; it is not a real SQL-engine integration test. Before deployment, apply install/upgrade/reinstall to a disposable copy of the real MySQL/MariaDB database, compare retained rows and custom module settings, and test standard/One-Page Checkout with all three categories, pickup preference, explicit delivery choice, changed categories/mileage/rates, unknown/zero mileage, and stale final-order inputs.
