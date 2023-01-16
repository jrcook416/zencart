<?php
/**
 * This is a file containing custom functions for the Indianapolis EMS implementation of Zen Cart.
 *
 *
 * Custom functions for Indianapolis EMS are defined in the /vector/includes/functions/extra_functions directory 
 * and the /includes/functions/extra_functions directory as per the Zen Cart coding standards.  This file should be
 * copied to each of those directories and maintained within Git version control.
 * For the sake of argument, this is the /includes/functions/extra_functions version of this file.
 * All code should be documented using phpDoc standards as laid out in the phpDoc manual and the 
 * IEMS documentation.
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 * 
 *
 * @package		IEMSCustomFiles
 * @subpackage	Vector
 * @category	Indianapolis EMS custom code
 * @link   		<https://www.iemssupply.net>
 * @author    	Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @copyright 	Copyright (c)2013-2022, Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @license   	<https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html> GNU General Public License 2
 * @version 	Jeremiah Cook 2022-09-20, modified for ZC v1.5.7d
 */

 function county_lookup() {
	global $db;
	global $county_array;

	$county_array = array();
	$county_values = $db->Execute("select * from iems_counties");
    	while (!$county_values->EOF) {
			$county_array[] = array(
			    'id' => $county_values->fields['countyID'],
			    'countyID' => $county_values->fields['countyID'],
			    'countyCode' => $county_values->fields['countyCode'],
			    'countyName' => $county_values->fields['countyName'],       
			    'text' => $county_values->fields['countyCode'] . " " . $county_values->fields['countyName']);
			$county_values->MoveNext();
			};
	return $county_array;	
	} //end county_array

function agency_lookup() {
	global $db;
	global $agency_array;

	$agency_array = array();
	$agency_values = $db->Execute("select * from iems_agencies");
		while (!$agency_values->EOF) {
			$agency_array[] = array(
			    'id' => $agency_values->fields['masterAgencyID'],
				'masterAgency' => $agency_values->fields['masterAgency'],
				'masterCountyID' => $agency_values->fields['masterCountyID'],
				'masterAgencyDescription' => $agency_values->fields['masterAgencyDescription'],
			    'text' => $agency_values->fields['masterAgency'] . " " . $agency_values->fields['masterAgencyDescription']);
			$agency_values->MoveNext();
			};
	return $agency_array; 
	} // end_agency_lookup

function unit_lookup() {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select * from iems_units");
		while (!$unit_values->EOF) {
			$unit_array[] = array(
					'id' => $unit_values->fields['masterUnitID'],
					'masterCountyID' => $unit_values->fields['masterCountyID'], 
					'masterAgencyID' => $unit_values->fields['masterAgencyID'],
					'masterAgency' => $unit_values->fields['masterAgency'],
					'masterUnitDescription' => $unit_values->fields['masterUnitDescription'], 
					'text' => $unit_values->fields['masterAgency'] . " " . $unit_values->fields['masterUnitDescription']);
			$unit_values->MoveNext();
			}; //end while
	return $unit_array; 
	} //end unit_lookup

function filtered_unit_lookup($filter) {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select * from iems_units where masterAgencyID = '" . $filter. "' order by masterUnitDescription");
		while (!$unit_values->EOF) {
			$unit_array[] = array(
					'id' => $unit_values->fields['masterUnitID'],
					'masterCountyID' => $unit_values->fields['masterCountyID'], 
					'masterAgencyID' => $unit_values->fields['masterAgencyID'],
					'masterAgency' => $unit_values->fields['masterAgency'],
					'masterUnitDescription' => $unit_values->fields['masterUnitDescription'], 
					'text' => $unit_values->fields['masterAgency'] . " " . $unit_values->fields['masterUnitDescription']);
			$unit_values->MoveNext();
			}; //end while
	return $unit_array; 
	} //end unit_lookup

  /**
 *  Output a form pull down menu
 *  Pulls values from a passed array, with the indicated option pre-selected
 * @param string $name name
 * @param array $values values
 * @param string $default default value
 * @param string $parameters parameters
 * @param boolean $required required
 * @return string
 */
function iems_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false)
{
  // -----
  // Give an observer the opportunity to **totally** override this function's operation.
  //
  $field = false;
  $GLOBALS['zco_notifier']->notify(
      'NOTIFY_ZEN_DRAW_PULL_DOWN_MENU_OVERRIDE',
      array(
        'name' => $name,
        'values' => $values,
        'default' => $default,
        'parameters' => $parameters,
        'required' => $required,
      ),
      $field
  );
  if ($field !== false) {
    return $field;
  }

  $field = '<select rel="dropdown"';
/*
  if (strpos($parameters, 'id=') === false) {
    $field .= ' id="select-' . zen_output_string($name) . '"';
  }
*/ 

  $field .= ' name="' . zen_output_string($name) . '"';

  if (zen_not_null($parameters)) {
    $field .= ' ' . $parameters;
  }

  $field .= '>' . "\n";

  if (empty($default) && isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) {
    $default = stripslashes($GLOBALS[$name]);
  }
  $field .= '<option value="">Please Select an Option</option>';
  foreach ($values as $value) {
    $field .= '  <option value="' . zen_output_string($value['id']) . '"';
    if ($default == $value['id']) {
      $field .= ' selected="selected"';
    }

    $field .= '>' . zen_output_string($value['text'], array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>' . "\n";
  }
  $field .= '</select>' . "\n";

  if ($required == true) {
     $field .= TEXT_FIELD_REQUIRED;
   }
  // -----
  // Give an observer the chance to make modifications to the just-rendered field.
  //
  $GLOBALS['zco_notifier']->notify(
      'NOTIFY_ZEN_DRAW_PULL_DOWN_MENU',
      array(
        'name' => $name,
        'values' => $values,
        'default' => $default,
        'parameters' => $parameters,
        'required' => $required,
      ),
      $field
  );
  return $field;
} // end iems_pull_down_menu()

function uom_lookup() {
	global $db;
	global $uom_array;
	$uom_array = array();
	$uom_values = $db->Execute("select uom_id, uom from `uom` ");

		while (!$uom_values->EOF) {
			$uom_array[] = array('id' => $uom_values->fields['uom'], 'text' => $uom_values->fields['uom']);
			$uom_values->MoveNext();
			};
	return $uom_array; 
	} //end uom_array

?>