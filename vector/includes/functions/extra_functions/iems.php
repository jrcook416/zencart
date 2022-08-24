<?php
/**
 * This is a file containing custom functions for the Indianapolis EMS implementation of Zen Cart.
 *
 *
 * Custom functions for Indianapolis EMS are defined in the /vector/includes/functions/extra_functions directory 
 * and the /includes/functions/extra_functions directory as per the Zen Cart coding standards.  This file should be
 * copied to each of those directories and maintained within Git version control.
 * For the sake of argument, this is the /vector/includes/functions/extra_functions version of this file.
 * All code should be documented using phpDoc standards as laid out in the phpDoc manual and the 
 * IEMS documentation.
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 * 
 *
 * @package		admin
 * @category	Indianapolis EMS custom code
 * @link   		<https://www.iemssupply.net>
 * @author    	Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @copyright 	Copyright (c)2013-2022, Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @license   	<https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html> GNU General Public License 2
 * @version 	Jeremiah Cook 2022-04-11, modified for ZC v1.5.7d
 */

/**
 * Queries the unit table in the database and returns an array of units.
 *
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 *
 * @return 	mixed An associative array ($unit_array) holding unit descriptions and unit filters.
 * @var		array $db - the database specified in /vector/includes/configure.php
 * @var		array $unit_array - the associative array that we will load the unit list into.
 * @var		string $unit_values - the string that holds the MySQL query to pull all columns from the `units` table.
 */
function unit_lookup() {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select * from `units`");
		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => $unit_values->fields['unit_description'], 'agency_filter' => $unit_values->fields['unit_filter']);
			$unit_values->MoveNext();
			}; //end while
	return $unit_array; 
	} //end unit_lookup

/**
 * Queries the unit table in the database and returns an array of units, filtered by the parameter $filter.
 *
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 *
 * @return 	mixed An associative array ($filtered_units) holding unit descriptions and unit filters.
 * @param  	string $filter - used to pass the selected agency to the function.  
 * @var		array $db - the database specified in /vector/includes/configure.php
 * @var		array $filtered_units - the associative array that we will load the unit list into.
 * @var		string $unit_values - the string that holds the MySQL query to pull all columns from the `units` table, filtered by the parameter $filter.
 */
function filtered_unit_array($filter) {
	global $db;
	global $filtered_units;
	global $filter;

	$filtered_units = array();
	$unit_values = $db->Execute("select unit_description from `units` where unit_filter LIKE '" .  $filter . "' order by unit_description");

		while (!$unit_values->EOF) {
			$filtered_units[] = array('id' => $unit_values->fields['unit_description'], 'text' => $unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $filtered_units; 
	} // end_filtered_unit_array

/**
 * Queries the unit table in the database and returns an array of distinct agency values.
 *
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 *
 * @return 	mixed An associative array ($company_array) holding distinct entries in the unit table in `unit_filter`.
 * @var		array $db - the database specified in /vector/includes/configure.php
 * @var		array $company_array - the associative array that we will load the unit filter (agency) list into.
 * @var		string $company_values - the string that holds the MySQL query to pull all distinct agency names from the `units` table.
 */
function company_lookup() {
	global $db;
	global $company_array;

	$company_array = array();
	$company_values = $db->Execute("select distinct unit_filter from `units` ");

		while (!$company_values->EOF) {
			$company_array[] = array('id' => $company_values->fields['unit_filter'], 'text' => $company_values->fields['unit_filter']);
			$company_values->MoveNext();
			};
	return $company_array; 
	} //end company_array
	
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

  $field = '<select rel="select"';

  if (strpos($parameters, 'id=') === false) {
    $field .= ' id="' . zen_output_string($name) . '"';
  }

  $field .= ' name="' . zen_output_string($name) . '"';

  if (zen_not_null($parameters)) {
    $field .= ' ' . $parameters;
  }

  $field .= '>' . "\n";

  if (empty($default) && isset($GLOBALS[$name]) && is_string($GLOBALS[$name])) {
    $default = stripslashes($GLOBALS[$name]);
  }

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
?>