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
- **Plugin lifecycle:** IemsCountyAgency v1.7.0 retains v1.6.0 `delivery_enabled`, adds/normalizes the three nullable unit address fields, and adds missing pickup-address settings when the pickup module is already installed. Plugin uninstall retains all IEMS reference fields and rows.

## 5) Branch acceptance criterion

- Standard GitHub Actions testing (existing suites: `zc_unit_test_suite.yml`,
  `zc_feature_test_admin_suite.yml`, `zc_feature_test_store_suite.yml`) must
  continue to pass; no new CI configuration is introduced by this branch.
