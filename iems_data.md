# IEMS Data Definitions

Reference documentation for the IEMS `county -> agency -> unit` data model
introduced in the `iems-county-agency-unit-foundation` branch. Schema source
of truth: `zc_install/sql/iems_custom/iems_foundation.sql`.

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

### `iems_units`
| Field | Type/Role |
|---|---|
| `unit_ID` | Auto-number primary key (explicit PK per repo convention) |
| `county_ID` | Foreign key -> `iems_counties.county_ID` |
| `agency_ID` | Foreign key -> `iems_agencies.agency_ID` |
| `unit_identifier` | Alphanumeric, max 10 characters |
| `unit_name` | Clean display name |

Each table also carries a `status` flag (active/inactive) and `date_added` /
`last_modified` timestamps, per the retention model in section 4.

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

## 5) Branch acceptance criterion

- Standard GitHub Actions testing (existing suites: `zc_unit_test_suite.yml`,
  `zc_feature_test_admin_suite.yml`, `zc_feature_test_store_suite.yml`) must
  continue to pass; no new CI configuration is introduced by this branch.
