<?php
/**
 * This file contains common functions used throughout the application.
 *
 * @package    	Vector (Administration) Files
 * @subpackage 	Functions
 * @author     	Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @license 	http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @copyright 	Copyright 2022 Indianapolis EMS Logistics
 * @copyright 	Portions Copyright 2003 osCommerce
 * @version 	Jeremiah Cook 2022-03-30, modified for ZC v1.5.7d
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