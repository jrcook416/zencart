<?php
/**
iems.php
Custom function definitions for Indianapolis EMS Zencart
File last edited 2026-06-27, Jeremiah Cook
**/ 

/**
function `unit_lookup`
global variables $db, $unit_array
Returns the $unit_array variable which includes a list of units pulled out of the dataset, commonly used in the create
account and order placing screens in Zencart. Introduced in older versions of IEMS Zencart.  This code drives all of the 
pull-down unit selects. CSS styling for this element is specifically noted in the template-specific CSS. 
**/ 

function unit_lookup() {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select unit_description from `iems_units` order by unit_description");

		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => $unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $unit_array; 
	}

/**
function county_lookup
global variables $db, $county_array
Returns the $county_array variable which includes a list of counties pulled out of the dataset, commonly also used in the create account
and order placing screens in Zencart.  Introduced in v3.0.0 and based on function unit_lookup above.  This code drives all of the pull-down
county selects.  CSS styling for this element is specifically noted in the template-specific CSS. 
**/

function county_lookup() {
	global $db;
	global $county_array;

	$county_array = array();
	$county_values = $db->Execute("select * from `iems_counties` order by iems_county_code");

		while (!$county_values->EOF) {
			$county_array[] = array(
                'id' => $county_values->fields['iems_county_ID'], 
                'countyCode' => $county_values->fields['iems_county_code'], 
                'text' => $county_values->fields['iems_county_name'],
                'idhsDistrict' => $county_values->fields['iems_idhs_district']);
			$county_values->MoveNext();
			};
	return $county_array; 
	}

function unit_lookup_filter() {
	global $db;
	global $unit_array;
	$filter = '49 IEMS';

	$unit_array = array();
	$unit_values = $db->Execute("select unit_description from `units` where unit_filter ='" .  $filter . "' order by unit_description");

		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => 			$unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $unit_array; 
	}

?>
