-- ============================================================================
-- IEMS Custom Schema: County / Agency / Unit Foundation
-- ============================================================================
-- Branch: iems-county-agency-unit-foundation
-- Purpose: Introduce the county -> agency -> unit reference hierarchy for
--          Indianapolis EMS Zen Cart customizations, per the v222-iems-dev
--          initiative handoff document (iems-handoff-2026-07-16.md).
--
-- IMPORTANT: This file is intentionally kept OUTSIDE of Zen Cart's core
-- zc_install/sql/updates/mysql_upgrade_zencart_XXX.sql patch sequence so it
-- is never overwritten/reordered by future core version upgrades. It is a
-- plugin/template-scoped, hand-run script for this dev environment.
--
-- Engine note: Zen Cart's core install tables use MyISAM (no FK support).
-- These three IEMS tables use InnoDB instead because the requirements
-- explicitly call for FK constraints with RESTRICT (delete) / CASCADE
-- (update) behavior, which MyISAM cannot enforce. This is a deliberate,
-- scoped deviation limited to these new custom tables only -- no existing
-- core table's engine is changed.
--
-- Status model: each table carries a `status` TINYINT(1) flag
-- (1 = active, 0 = inactive) instead of hard deletes, so historical
-- customer/order/address assignments referencing an inactive county,
-- agency, or unit remain valid. Application-layer lookup functions (added
-- in a later branch) are responsible for filtering `WHERE status = 1` when
-- populating NEW-selection dropdowns; this SQL-only branch does not touch
-- PHP lookup code.
--
-- Audit trail: `iems_import_log` records who/when/what-file-version for
-- each bulk data import performed via this file or subsequent seed files.
-- See the note near the bottom of this file for a known gap regarding
-- true per-admin-user attribution.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: iems_counties
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iems_counties` (
  `county_ID` int(11) NOT NULL AUTO_INCREMENT,
  `county_number` varchar(4) NOT NULL COMMENT 'Indiana state county code',
  `county_name` varchar(64) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active/selectable, 0 = inactive (retained for historical references)',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_modified` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`county_ID`),
  UNIQUE KEY `idx_iems_counties_number` (`county_number`),
  UNIQUE KEY `idx_iems_counties_name` (`county_name`),
  KEY `idx_iems_counties_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------------
-- Table: iems_agencies
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iems_agencies` (
  `agency_ID` int(11) NOT NULL AUTO_INCREMENT,
  `county_ID` int(11) NOT NULL,
  `agency_identifier` varchar(10) NOT NULL COMMENT 'Uppercase alphanumeric, max 10 chars',
  `agency_name` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active/selectable, 0 = inactive (retained for historical references)',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_modified` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`agency_ID`),
  UNIQUE KEY `idx_iems_agencies_county_identifier` (`county_ID`, `agency_identifier`),
  KEY `idx_iems_agencies_status` (`status`),
  CONSTRAINT `fk_iems_agencies_county` FOREIGN KEY (`county_ID`)
    REFERENCES `iems_counties` (`county_ID`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------------
-- Table: iems_units
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iems_units` (
  `unit_ID` int(11) NOT NULL AUTO_INCREMENT,
  `county_ID` int(11) NOT NULL,
  `agency_ID` int(11) NOT NULL,
  `unit_identifier` varchar(10) NOT NULL COMMENT 'Uppercase alphanumeric, max 10 chars',
  `unit_name` varchar(128) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = active/selectable, 0 = inactive (retained for historical references)',
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_modified` datetime NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`unit_ID`),
  UNIQUE KEY `idx_iems_units_agency_identifier` (`agency_ID`, `unit_identifier`),
  KEY `idx_iems_units_county` (`county_ID`),
  KEY `idx_iems_units_status` (`status`),
  CONSTRAINT `fk_iems_units_county` FOREIGN KEY (`county_ID`)
    REFERENCES `iems_counties` (`county_ID`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_iems_units_agency` FOREIGN KEY (`agency_ID`)
    REFERENCES `iems_agencies` (`agency_ID`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------------------------------------------------------
-- Table: iems_import_log (audit trail for bulk data imports)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iems_import_log` (
  `import_ID` int(11) NOT NULL AUTO_INCREMENT,
  `table_name` varchar(64) NOT NULL,
  `source_file` varchar(255) NOT NULL,
  `file_version` varchar(32) NULL DEFAULT NULL,
  `imported_by` varchar(96) NOT NULL DEFAULT 'system',
  `imported_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rows_affected` int(11) NOT NULL DEFAULT 0,
  `notes` text NULL,
  PRIMARY KEY (`import_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ============================================================================
-- Seed data: full Indiana county list (92 counties), standard alphabetical
-- state county-code numbering (e.g. 49 = Marion), matching selector contract
-- "<county_number> <county_name>" (example: "49 Marion").
-- ============================================================================
INSERT INTO `iems_counties` (`county_ID`, `county_number`, `county_name`) VALUES
(1, '1', 'Adams'),
(2, '2', 'Allen'),
(3, '3', 'Bartholomew'),
(4, '4', 'Benton'),
(5, '5', 'Blackford'),
(6, '6', 'Boone'),
(7, '7', 'Brown'),
(8, '8', 'Carroll'),
(9, '9', 'Cass'),
(10, '10', 'Clark'),
(11, '11', 'Clay'),
(12, '12', 'Clinton'),
(13, '13', 'Crawford'),
(14, '14', 'Daviess'),
(15, '15', 'Dearborn'),
(16, '16', 'Decatur'),
(17, '17', 'DeKalb'),
(18, '18', 'Delaware'),
(19, '19', 'Dubois'),
(20, '20', 'Elkhart'),
(21, '21', 'Fayette'),
(22, '22', 'Floyd'),
(23, '23', 'Fountain'),
(24, '24', 'Franklin'),
(25, '25', 'Fulton'),
(26, '26', 'Gibson'),
(27, '27', 'Grant'),
(28, '28', 'Greene'),
(29, '29', 'Hamilton'),
(30, '30', 'Hancock'),
(31, '31', 'Harrison'),
(32, '32', 'Hendricks'),
(33, '33', 'Henry'),
(34, '34', 'Howard'),
(35, '35', 'Huntington'),
(36, '36', 'Jackson'),
(37, '37', 'Jasper'),
(38, '38', 'Jay'),
(39, '39', 'Jefferson'),
(40, '40', 'Jennings'),
(41, '41', 'Johnson'),
(42, '42', 'Knox'),
(43, '43', 'Kosciusko'),
(44, '44', 'LaGrange'),
(45, '45', 'Lake'),
(46, '46', 'LaPorte'),
(47, '47', 'Lawrence'),
(48, '48', 'Madison'),
(49, '49', 'Marion'),
(50, '50', 'Marshall'),
(51, '51', 'Martin'),
(52, '52', 'Miami'),
(53, '53', 'Monroe'),
(54, '54', 'Montgomery'),
(55, '55', 'Morgan'),
(56, '56', 'Newton'),
(57, '57', 'Noble'),
(58, '58', 'Ohio'),
(59, '59', 'Orange'),
(60, '60', 'Owen'),
(61, '61', 'Parke'),
(62, '62', 'Perry'),
(63, '63', 'Pike'),
(64, '64', 'Porter'),
(65, '65', 'Posey'),
(66, '66', 'Pulaski'),
(67, '67', 'Putnam'),
(68, '68', 'Randolph'),
(69, '69', 'Ripley'),
(70, '70', 'Rush'),
(71, '71', 'St. Joseph'),
(72, '72', 'Scott'),
(73, '73', 'Shelby'),
(74, '74', 'Spencer'),
(75, '75', 'Starke'),
(76, '76', 'Steuben'),
(77, '77', 'Sullivan'),
(78, '78', 'Switzerland'),
(79, '79', 'Tippecanoe'),
(80, '80', 'Tipton'),
(81, '81', 'Union'),
(82, '82', 'Vanderburgh'),
(83, '83', 'Vermillion'),
(84, '84', 'Vigo'),
(85, '85', 'Wabash'),
(86, '86', 'Warren'),
(87, '87', 'Warrick'),
(88, '88', 'Washington'),
(89, '89', 'Wayne'),
(90, '90', 'Wells'),
(91, '91', 'White'),
(92, '92', 'Whitley')
ON DUPLICATE KEY UPDATE `county_name` = VALUES(`county_name`);

-- ============================================================================
-- Seed data: iems_agencies / iems_units
-- ============================================================================
-- NO SEED ROWS ARE INCLUDED for iems_agencies or iems_units in this branch.
-- No authoritative agency/unit dataset exists anywhere in this repository
-- (checked: no CSV/SQL/data files referencing agency or unit lists beyond
-- the county-code mapping above). Fabricating agency/unit names would risk
-- introducing incorrect real-world EMS/fire agency data. Schema for both
-- tables is fully in place and ready to receive a real dataset via a
-- follow-up seed file (e.g. `iems_agencies_seed.sql`, `iems_units_seed.sql`)
-- once the actual county->agency->unit list is supplied.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Audit log entry for this import run
-- ----------------------------------------------------------------------------
INSERT INTO `iems_import_log`
  (`table_name`, `source_file`, `file_version`, `imported_by`, `rows_affected`, `notes`)
VALUES
  ('iems_counties', 'zc_install/sql/iems_custom/iems_foundation.sql', '2026-07-16-01', 'dev-seed', 92,
   'Full Indiana county reference list (92 counties), standard alphabetical state county-code numbering.'),
  ('iems_agencies', 'zc_install/sql/iems_custom/iems_foundation.sql', '2026-07-16-01', 'dev-seed', 0,
   'Schema only -- no rows imported. Awaiting authoritative agency dataset.'),
  ('iems_units', 'zc_install/sql/iems_custom/iems_foundation.sql', '2026-07-16-01', 'dev-seed', 0,
   'Schema only -- no rows imported. Awaiting authoritative unit dataset.');
