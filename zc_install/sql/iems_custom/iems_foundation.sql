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
-- state county-code numbering, zero-padded to 2 digits (e.g. 49 = Marion,
-- 03 = Bartholomew) to match the zero-padded convention used in real IEMS
-- agency data, matching selector contract "<county_number> <county_name>"
-- (example: "49 Marion").
-- ============================================================================
INSERT INTO `iems_counties` (`county_ID`, `county_number`, `county_name`) VALUES
(1, '01', 'Adams'),
(2, '02', 'Allen'),
(3, '03', 'Bartholomew'),
(4, '04', 'Benton'),
(5, '05', 'Blackford'),
(6, '06', 'Boone'),
(7, '07', 'Brown'),
(8, '08', 'Carroll'),
(9, '09', 'Cass'),
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
-- Seed data: iems_agencies
-- ============================================================================
-- Imported from a legacy `iems_agencies_old` phpMyAdmin dump (123 rows,
-- old agency_id 4-126). Mapped from the legacy shape
-- (agency_id, agency_countyID, agency_filter, agency_customer_group,
-- agency_description, agency_address) to the new schema
-- (agency_ID, county_ID, agency_identifier, agency_name) as follows:
--   - agency_countyID -> resolved to county_ID via a join on county_number
--     (zero-padded to 2 digits, e.g. '03', '49').
--   - agency_description, formatted as "<county_number> <IDENTIFIER> <name>",
--     split into agency_identifier and agency_name.
--   - agency_filter, agency_customer_group, agency_address: dropped. Not
--     part of the new schema; customer-group derivation is handled
--     separately per the county+agency model (see iems_data.md governance
--     notes / handoff doc Branch 4).
--
-- Data corrections applied during import, confirmed with the data owner:
--   - old agency_id 17 ("29 CFD Cicero Fire Department"): legacy
--     agency_countyID was 28 (Greene), but Cicero is in Hamilton County
--     (29), matching the surrounding Hamilton-county cluster and the
--     description text. Corrected to county 29.
--   - old agency_id 72 ("49 EHS Security"): duplicated the 'EHS' identifier
--     already used by "Eskenazi Health Services" (agency_id 71) in the same
--     county, which is not allowed under the new per-county uniqueness
--     constraint. Renamed to identifier 'EHPD', name "Eskenazi Health
--     Police Department".
--   - old agency_id 73 ("49 Franciscan Health EMS Education"): had no
--     distinct short identifier in the legacy free-text description.
--     Assigned identifier 'FH'.
-- ============================================================================
INSERT INTO `iems_agencies` (`county_ID`, `agency_identifier`, `agency_name`)
SELECT c.`county_ID`, v.`agency_identifier`, v.`agency_name`
FROM (
  SELECT '03' AS county_number, 'ECIFD' AS agency_identifier, 'East Columbus Independent Fire Department' AS agency_name
  UNION ALL SELECT '03' AS county_number, 'FSSLC' AS agency_identifier, 'Four Seasons Senior Living Community' AS agency_name
  UNION ALL SELECT '06' AS county_number, 'AVFD' AS agency_identifier, 'Advance Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '06' AS county_number, 'HVSLC' AS agency_identifier, 'Hoosier Village Senior Living Community' AS agency_name
  UNION ALL SELECT '06' AS county_number, 'WFD' AS agency_identifier, 'Whitestown Fire Department' AS agency_name
  UNION ALL SELECT '06' AS county_number, 'WMPD' AS agency_identifier, 'Whitestown Metropolitan Police Department' AS agency_name
  UNION ALL SELECT '06' AS county_number, 'ZFD' AS agency_identifier, 'Zionsville Fire Department' AS agency_name
  UNION ALL SELECT '12' AS county_number, 'RVAS' AS agency_identifier, 'Rossville Volunteer Ambulance Service' AS agency_name
  UNION ALL SELECT '21' AS county_number, 'FCEMS' AS agency_identifier, 'Fayette County EMS' AS agency_name
  UNION ALL SELECT '27' AS county_number, 'CTVFD' AS agency_identifier, 'Center Township Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '27' AS county_number, 'MTFD' AS agency_identifier, 'Mill Township Fire Department' AS agency_name
  UNION ALL SELECT '28' AS county_number, 'CJFT' AS agency_identifier, 'Center-Jackson Fire Territory' AS agency_name
  UNION ALL SELECT '28' AS county_number, 'GCAS' AS agency_identifier, 'Greene County Ambulance service' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'CFD' AS agency_identifier, 'Cicero Fire Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'CFHD' AS agency_identifier, 'City of Fishers Health Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'CP' AS agency_identifier, 'Conner Prairie' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'CPD' AS agency_identifier, 'Carmel Police Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'FFD' AS agency_identifier, 'Fishers Fire Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'JTFD' AS agency_identifier, 'Jackson Township Fire Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'SFD' AS agency_identifier, 'Sheridan Fire Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'WFD' AS agency_identifier, 'Westfield Fire Department' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'WRTFD' AS agency_identifier, 'White River Township Fire Department (Hamilton)' AS agency_name
  UNION ALL SELECT '29' AS county_number, 'WTVFD' AS agency_identifier, 'Wayne Township Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '30' AS county_number, 'BCTFD' AS agency_identifier, 'Buck Creek Township Fire Department' AS agency_name
  UNION ALL SELECT '30' AS county_number, 'SCTFD' AS agency_identifier, 'Sugar Creek Township Fire Department' AS agency_name
  UNION ALL SELECT '30' AS county_number, 'VTFD' AS agency_identifier, 'Vernon Township Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'AVFD' AS agency_identifier, 'Amo Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'BFT' AS agency_identifier, 'Brownsburg Fire Territory' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'CFD' AS agency_identifier, 'Coatesville Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'DFD' AS agency_identifier, 'Danville Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'ERTFD' AS agency_identifier, 'Eel River Township Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'FDLT' AS agency_identifier, 'Fire Department of Liberty Township' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'HCCO' AS agency_identifier, 'Hendricks County Coroner''s Office' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'LFD' AS agency_identifier, 'Lizton Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'LPD' AS agency_identifier, 'Lizton Police Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'NSFD' AS agency_identifier, 'North Salem Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'PFD' AS agency_identifier, 'Pittsboro Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'PFTHQ' AS agency_identifier, 'Plainfield Fire Territory' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'SVFD' AS agency_identifier, 'Stilesville Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '32' AS county_number, 'WTAFD' AS agency_identifier, 'Washington Township/Avon Fire Department' AS agency_name
  UNION ALL SELECT '34' AS county_number, 'HCEM' AS agency_identifier, 'Howard County Emergency Management' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'BFD' AS agency_identifier, 'Bargersville Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'CSVFD' AS agency_identifier, 'Cordry-Sweetwater Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'EFD' AS agency_identifier, 'Edinburgh Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'FFD' AS agency_identifier, 'Franklin Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'GFD' AS agency_identifier, 'Greenwood Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'NCVFD' AS agency_identifier, 'Needham Community Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'NTVFD' AS agency_identifier, 'Nineveh Township Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'NWFD' AS agency_identifier, 'New Whiteland Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'PLPD' AS agency_identifier, 'Princes Lakes Police Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'TFD' AS agency_identifier, 'Trafalgar Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'WRTFD' AS agency_identifier, 'White River Township Fire Department' AS agency_name
  UNION ALL SELECT '41' AS county_number, 'WVFD' AS agency_identifier, 'Whiteland Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'AMFPT' AS agency_identifier, 'Adams Markleville Fire Protection Territory' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'CUTFD' AS agency_identifier, 'Chesterfield Union Township Fire Department' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'EVFD' AS agency_identifier, 'Edgewood Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'FVAS' AS agency_identifier, 'Frankton Volunteer Ambulance Service' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'LSCTFT' AS agency_identifier, 'Lapel Stony Creek Township Fire Territory' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'PEAI' AS agency_identifier, 'Pendleton Emergency Ambulance Incorporated' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'PVFD' AS agency_identifier, 'Pendleton Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '48' AS county_number, 'RTFD' AS agency_identifier, 'Richland Township Fire Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'AIC' AS agency_identifier, 'Assessment and Intervention Center' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'ARC' AS agency_identifier, 'American Red Cross' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'BGEMS' AS agency_identifier, 'City of Beech Grove EMS' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'CLPD' AS agency_identifier, 'City of Lawrence Police Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'DTFDHQ' AS agency_identifier, 'Decatur Township Headquarters' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'EHPT' AS agency_identifier, 'Eskenazi Transportation Services' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'EHS' AS agency_identifier, 'Eskenazi Health Services' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'EHPD' AS agency_identifier, 'Eskenazi Health Police Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'FH' AS agency_identifier, 'Franciscan Health EMS Education' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'Indianapolis EMS' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'IFD' AS agency_identifier, 'Indianapolis Fire Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'IIAFD' AS agency_identifier, 'Indianapolis International Airport Fire Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'IMPD' AS agency_identifier, 'Indianapolis Metropolitan Police Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'IN-TF1' AS agency_identifier, 'Indiana Task Force One' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'IPSF' AS agency_identifier, 'Indianapolis Public Safety Foundation' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'ISDH' AS agency_identifier, 'Indiana State Department of Health' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'JELCC' AS agency_identifier, 'J. Everett Light Career Center' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'LFD' AS agency_identifier, 'City of Lawrence Fire Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'MCCC' AS agency_identifier, 'Marion County Community Corrections' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'MESH' AS agency_identifier, 'MESH Coalition' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'MSDPT' AS agency_identifier, 'Metropolitan School District of Pike Township' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'OPHS' AS agency_identifier, 'Office of Public Health and Safety' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'PSC' AS agency_identifier, 'Public Safety Communications' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'PTFD' AS agency_identifier, 'Pike Township Fire Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'ROC' AS agency_identifier, 'Regional Operations Center' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'SCC' AS agency_identifier, 'Shepherd Community Center' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'SFD' AS agency_identifier, 'Town of Speedway Fire Department' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'SLEH' AS agency_identifier, 'Sidney and Lois Eskenazi Hospital' AS agency_name
  UNION ALL SELECT '49' AS county_number, 'TCA' AS agency_identifier, 'TransCare Ambulance' AS agency_name
  UNION ALL SELECT '53' AS county_number, 'HHCC' AS agency_identifier, 'Hoosier Hills Career Center' AS agency_name
  UNION ALL SELECT '54' AS county_number, 'CFD' AS agency_identifier, 'Crawfordsville Fire Department' AS agency_name
  UNION ALL SELECT '54' AS county_number, 'LR' AS agency_identifier, 'Ladoga Rescue' AS agency_name
  UNION ALL SELECT '54' AS county_number, 'WTFD' AS agency_identifier, 'Walnut Township Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'AAVFD' AS agency_identifier, 'Adams and Ashland Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'BTFD' AS agency_identifier, 'Brown Township Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'BVFD' AS agency_identifier, 'Brooklyn Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'GTFD' AS agency_identifier, 'Gregg Township Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'GTFR' AS agency_identifier, 'Green Township Fire Rescue' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'HTFD' AS agency_identifier, 'Harrison Township Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MAFD' AS agency_identifier, 'Martinsville Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MCEMA' AS agency_identifier, 'Morgan County Emergency Management Agency' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MCEMS' AS agency_identifier, 'Morgan County EMS' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MCSD' AS agency_identifier, 'Morgan County Sheriff''s Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MFD' AS agency_identifier, 'Mooresville Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MOFD' AS agency_identifier, 'Morgantown Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MOTFD' AS agency_identifier, 'Monroe Township Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MSDMH' AS agency_identifier, 'MSD of Martinsville' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'MTFD' AS agency_identifier, 'Madison Township Fire Department' AS agency_name
  UNION ALL SELECT '55' AS county_number, 'WTFD' AS agency_identifier, 'Washington Township Fire Department' AS agency_name
  UNION ALL SELECT '64' AS county_number, 'VFD' AS agency_identifier, 'Valparaiso Fire Department' AS agency_name
  UNION ALL SELECT '67' AS county_number, 'A30CC' AS agency_identifier, 'Area 30 Career Center' AS agency_name
  UNION ALL SELECT '67' AS county_number, 'FTFD' AS agency_identifier, 'Floyd Township Fire Department' AS agency_name
  UNION ALL SELECT '67' AS county_number, 'PCEMS' AS agency_identifier, 'Putnam County EMS' AS agency_name
  UNION ALL SELECT '70' AS county_number, 'CVFD' AS agency_identifier, 'Carthage Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '73' AS county_number, 'SFD' AS agency_identifier, 'Shelbyville Fire Department' AS agency_name
  UNION ALL SELECT '75' AS county_number, 'SCEMS' AS agency_identifier, 'Starke County EMS' AS agency_name
  UNION ALL SELECT '79' AS county_number, 'CHLVFD' AS agency_identifier, 'Clarks Hill-Lauramie Volunteer Fire Department' AS agency_name
  UNION ALL SELECT '79' AS county_number, 'TCEMA' AS agency_identifier, 'Tippecanoe County Emergency Management Agency' AS agency_name
  UNION ALL SELECT '79' AS county_number, 'TEAS' AS agency_identifier, 'Tippecanoe Emergency Ambulance Service' AS agency_name
  UNION ALL SELECT '79' AS county_number, 'WTFD' AS agency_identifier, 'Wabash Township Fire Department' AS agency_name
  UNION ALL SELECT '81' AS county_number, 'UCEMA' AS agency_identifier, 'Union County Emergency Management Agency' AS agency_name
  UNION ALL SELECT '89' AS county_number, 'RLEMS' AS agency_identifier, 'Red Line EMS' AS agency_name
) v
JOIN `iems_counties` c ON c.`county_number` = v.`county_number`;

-- ============================================================================
-- Seed data: iems_units
-- ============================================================================
-- Imported and reconciled from legacy iems_units_old dump (146 rows), all for
-- Marion County (49). Only rows with a distinct legacy unit code are included
-- in this pass (109 rows). See reconciliation notes above the INSERT block
-- and the import log entry below for the full list of corrections/decisions,
-- all confirmed with the data owner.
--
-- DEFERRED (not imported in this pass): 32 rows that never had a distinct
-- unit code in the legacy data (pure location/functional names only, e.g.
-- EHS clinic locations such as "Pecar CHC", "Blackburn CHC"; IEMS functional
-- units like "Safety Officer", "Logistics", "Indiana State Fair"; and the
-- self-referential IPSF/AIC/IMPD/SLEH rows where the "unit" is just the
-- agency's own name). The data owner will assign unit_identifier values for
-- these case-by-case in a follow-up pass -- do not auto-generate codes for
-- them.
-- ============================================================================

INSERT INTO iems_units (county_ID, agency_ID, unit_identifier, unit_name)
SELECT c.county_ID, a.agency_ID, v.unit_identifier, v.unit_name
FROM (
  SELECT '49' AS county_number, 'EHPT' AS agency_identifier, 'AMB001' AS unit_identifier, 'Eskenazi Transportation Services Ambulance 001' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'EHPT' AS agency_identifier, 'AMB002' AS unit_identifier, 'Eskenazi Transportation Services Ambulance 002' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'EHPT' AS agency_identifier, 'AMB003' AS unit_identifier, 'Eskenazi Transportation Services Ambulance 003' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'EHPT' AS agency_identifier, 'AMB004' AS unit_identifier, 'Eskenazi Transportation Services Ambulance 004' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM001' AS unit_identifier, 'IEMS Ambulance 001' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM003' AS unit_identifier, 'IEMS Ambulance 003' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM005' AS unit_identifier, 'IEMS Ambulance 005' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM011' AS unit_identifier, 'IEMS Ambulance 011' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM013' AS unit_identifier, 'IEMS Ambulance 013' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM015' AS unit_identifier, 'IEMS Ambulance 015' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM019' AS unit_identifier, 'IEMS Ambulance 019' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM020' AS unit_identifier, 'IEMS Ambulance 020' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM022' AS unit_identifier, 'IEMS Ambulance 022' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM025' AS unit_identifier, 'IEMS Ambulance 025' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM027' AS unit_identifier, 'IEMS Ambulance 027' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM043' AS unit_identifier, 'IEMS Ambulance 043' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM046' AS unit_identifier, 'IEMS Ambulance 046' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM051' AS unit_identifier, 'IEMS Ambulance 051' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM052' AS unit_identifier, 'IEMS Ambulance 052' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM092' AS unit_identifier, 'IEMS Ambulance 092' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'AM099' AS unit_identifier, 'IEMS Ambulance 099' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'DST1' AS unit_identifier, 'IEMS District 1' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'DST2' AS unit_identifier, 'IEMS District 2' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'DST3' AS unit_identifier, 'IEMS District 3' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'DST4' AS unit_identifier, 'IEMS District 4' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'DST6' AS unit_identifier, 'IEMS District 6' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS027' AS unit_identifier, 'IEMS District Medic EMS027' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS051' AS unit_identifier, 'IEMS District Medic EMS051' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS053' AS unit_identifier, 'IEMS District Medic EMS053' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS059' AS unit_identifier, 'IEMS District Medic EMS059' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS091' AS unit_identifier, 'IEMS District Medic EMS091' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS092' AS unit_identifier, 'IEMS District Medic EMS092' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS093' AS unit_identifier, 'IEMS District Medic EMS093' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'EMS094' AS unit_identifier, 'IEMS District Medic EMS094' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'FED_TEMS' AS unit_identifier, 'State Federal TEMS' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'IEMSET' AS unit_identifier, 'IEMS Education and Training' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'IEMS_MIH' AS unit_identifier, 'IEMS Mobile Integrated Healthcare' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'IEMS_TERG' AS unit_identifier, 'IEMS ERG TEMS' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'IEMS_TIMPD' AS unit_identifier, 'IEMS IMPD TEMS' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD000' AS unit_identifier, 'IEMS Medic 000' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD001' AS unit_identifier, 'IEMS Medic 001' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD002' AS unit_identifier, 'IEMS Medic 002' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD003' AS unit_identifier, 'IEMS Medic 003' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD004' AS unit_identifier, 'IEMS Medic 004' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD004R' AS unit_identifier, 'IEMS Reserve Medic 004' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD005' AS unit_identifier, 'IEMS Medic 005' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD005R' AS unit_identifier, 'IEMS Reserve Medic 005' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD009' AS unit_identifier, 'IEMS Medic 009' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD010' AS unit_identifier, 'IEMS Medic 010' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD011' AS unit_identifier, 'IEMS Medic 011' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD011R' AS unit_identifier, 'IEMS Reserve Medic 011' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD012' AS unit_identifier, 'IEMS Medic 012' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD012R' AS unit_identifier, 'IEMS Reserve Medic 012' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD013' AS unit_identifier, 'IEMS Medic 013' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD014' AS unit_identifier, 'IEMS Medic 014' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD015' AS unit_identifier, 'IEMS Medic 015' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD016' AS unit_identifier, 'IEMS Medic 016' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD017' AS unit_identifier, 'IEMS Medic 017' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD018' AS unit_identifier, 'IEMS Medic 018' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD019' AS unit_identifier, 'IEMS Medic 019' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD020' AS unit_identifier, 'IEMS Medic 020' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD021' AS unit_identifier, 'IEMS Medic 021' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD021R' AS unit_identifier, 'IEMS Reserve Medic 021' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD022' AS unit_identifier, 'IEMS Medic 022' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD023' AS unit_identifier, 'IEMS Medic 023' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD023R' AS unit_identifier, 'IEMS Reserve Medic 023' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD024' AS unit_identifier, 'IEMS Medic 024' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD025' AS unit_identifier, 'IEMS Medic 025' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD026' AS unit_identifier, 'IEMS Medic 026' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD026R' AS unit_identifier, 'IEMS Reserve Medic 026' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD027' AS unit_identifier, 'IEMS Medic 027' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD029' AS unit_identifier, 'IEMS Medic 029' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD029R' AS unit_identifier, 'IEMS Reserve Medic 029' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD030' AS unit_identifier, 'IEMS Medic 030' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD030R' AS unit_identifier, 'IEMS Reserve Medic 030' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD031' AS unit_identifier, 'IEMS Medic 031' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD032' AS unit_identifier, 'IEMS Medic 032' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD035' AS unit_identifier, 'IEMS Medic 035' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD041' AS unit_identifier, 'IEMS Medic 041' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD042' AS unit_identifier, 'IEMS Medic 042' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD044' AS unit_identifier, 'IEMS Medic 044' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD044R' AS unit_identifier, 'IEMS Reserve Medic 044' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD045' AS unit_identifier, 'IEMS Medic 045' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD046' AS unit_identifier, 'IEMS Medic 046' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD047' AS unit_identifier, 'IEMS Medic 047' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD048' AS unit_identifier, 'IEMS Medic 048' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD049' AS unit_identifier, 'IEMS Medic 049' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD051' AS unit_identifier, 'IEMS Medic 051' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD052' AS unit_identifier, 'IEMS Medic 052' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD053' AS unit_identifier, 'IEMS Medic 053' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD053R' AS unit_identifier, 'IEMS Reserve Medic 053' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD054' AS unit_identifier, 'IEMS Medic 054' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD081' AS unit_identifier, 'IEMS Medic 081' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD082' AS unit_identifier, 'IEMS Medic 082' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD083' AS unit_identifier, 'IEMS Medic 083' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD084' AS unit_identifier, 'IEMS Medic 084' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD085' AS unit_identifier, 'IEMS Medic 085' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD089' AS unit_identifier, 'IEMS Medic 089' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD089R' AS unit_identifier, 'IEMS Reserve Medic 089' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD091' AS unit_identifier, 'IEMS Medic 91 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD092' AS unit_identifier, 'IEMS Medic 92 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD093' AS unit_identifier, 'IEMS Medic 93 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD094' AS unit_identifier, 'IEMS Medic 94 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD095' AS unit_identifier, 'IEMS Medic 95 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD096' AS unit_identifier, 'IEMS Medic 96 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD097' AS unit_identifier, 'IEMS Medic 97 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD098' AS unit_identifier, 'IEMS Medic 98 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'MD099' AS unit_identifier, 'IEMS Medic 99 SET' AS unit_name
  UNION ALL SELECT '49' AS county_number, 'IEMS' AS agency_identifier, 'OC' AS unit_identifier, 'IEMS Operations Command' AS unit_name
) v
JOIN `iems_counties` c ON c.`county_number` = v.`county_number`
JOIN `iems_agencies` a ON a.`county_ID` = c.`county_ID` AND a.`agency_identifier` = v.`agency_identifier`;

-- ----------------------------------------------------------------------------
-- Audit log entry for this import run
-- ----------------------------------------------------------------------------
INSERT INTO `iems_import_log`
  (`table_name`, `source_file`, `file_version`, `imported_by`, `rows_affected`, `notes`)
VALUES
  ('iems_counties', 'zc_install/sql/iems_custom/iems_foundation.sql', '2026-07-16-02', 'dev-seed', 92,
   'Full Indiana county reference list (92 counties), zero-padded 2-digit state county-code numbering.'),
  ('iems_agencies', 'zc_install/sql/iems_custom/iems_foundation.sql', '2026-07-16-02', 'dev-seed', 123,
   'Imported from legacy iems_agencies_old dump (123 rows). 3 data corrections applied and confirmed with data owner: agency_id 17 county corrected 28->29 (Cicero is in Hamilton County); agency_id 72 identifier changed EHS->EHPD (Eskenazi Health Police Department, to resolve duplicate EHS identifier); agency_id 73 assigned identifier FH (Franciscan Health EMS Education, had no distinct code in source).'),
  ('iems_units', 'zc_install/sql/iems_custom/iems_foundation.sql', '2026-07-16-03', 'dev-seed', 109,
   'Imported from legacy iems_units_old dump (146 rows), Marion County only. Reconciliation confirmed with data owner: excluded old_id=1 UI placeholder row (blank county/agency); deduped exact-duplicate MD019 row (old ids 101/102); dropped 3 superseded "(OLD)" rows for MD097/MD098/MD099, keeping the "SET" successor rows; shortened oversized code IEMS_TFEDIN (11 chars) to FED_TEMS (old_id 81); kept "SET" suffix in unit_name for MD091-MD099 as directed. 32 rows with no distinct legacy unit code (pure location/functional names, e.g. EHS clinic sites, IPSF/AIC/IMPD/SLEH self-referential rows) are DEFERRED -- data owner will assign unit_identifier values case-by-case in a follow-up pass; not auto-generated.');

