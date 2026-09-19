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
| `county_number` | Indiana state county code, zero-padded to 2 digits (e.g. `01`, `03`, `49`) to match the convention used in real IEMS agency records |
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
| County | `county_ID` | `<county_number> <county_name>` | `49 Marion` |
| Agency | `agency_ID` | `<county_number> <agency_identifier> <agency_name>` | `49 IEMS Indianapolis EMS` |
| Unit | `unit_ID` | `<county_number> <agency_identifier> <unit_identifier> <unit_name>` | `49 IEMS MED1 Medic 1` |

Dependency flow: **county -> agency -> unit** (agency options are filtered by
selected county; unit options are filtered by selected agency).

## 4) Governance decisions

- **System-of-record owner:** IEMS superusers/admin ops.
- **Unit address owner:** Authorized IEMS unit administrators maintain delivery street, city, and postcode through **Customers > IEMS Units**. The three values are all blank or all nonblank. State and country are not stored per unit; runtime resolves Indiana and United States by stable `IN`/`US` codes.
- **Shipping eligibility owner:** Agency administrators maintain `delivery_enabled` through **Customers > IEMS Agencies**. Pickup requires a confirmed current unit or explicit zero-unit agency fallback. Delivery additionally requires this flag, a real active unit selection, and that unit's complete managed address.
- **Fail-closed shipping:** Guest, unaffiliated, inactive, cross-county/inconsistent, duplicate, malformed, stale, partial-address, missing-schema, or invalid fixed-region states do not expose an invalid IEMS method. An inactive county, agency, or unit is never made eligible by retained data or `delivery_enabled`.
- **Pickup destination:** The `iemspickup` module owns recipient/location name, optional company, street, city, and postcode configuration. Required fields must be complete and fit order-column lengths. Indiana/United States remain fixed and code-resolved.
- **Shipping cost:** Both Pickup at IEMS Logistics and Delivery to Location are hardcoded to `0.00`; configuration cannot introduce a charge.
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
- **Plugin lifecycle:** IemsCountyAgency v1.8.0 retains prior shipping/address fields, adds or normalizes `payment_mode`, and defaults all unsupported legacy values to `invoice`. Plugin uninstall removes custom shipping/payment module configuration while retaining all IEMS reference fields and rows.

## 5) Shipping deployment/operations checklist

- Install and enable **Delivery to Location** (`iemsdelivery`) under **Modules > Shipping**.
- Under **Customers > IEMS Agencies**, keep the selected unit's parent agency active and set **Delivery Enabled**. Delivery authorization is agency-level, not a unit setting.
- Under **Customers > IEMS Units**, keep the selected real unit active and provide complete street, city, and postcode values.
- Install and enable **Pickup at IEMS Logistics** (`iemspickup`), then configure its recipient/location, street, city, and postcode. Pickup is withheld if any required value is missing.
- Treat the explicit zero-unit agency fallback as pickup-only; it never qualifies for Delivery to Location.
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
