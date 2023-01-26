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
    function filtered_unit_lookup($agency) {
        global $db;
        global $filtered_unit_array;

        $filtered_unit_array = array();
        $filtered_unit_values = $db->Execute("select * from iems_units where masterAgencyID = '" . $agency . "'");
            while (!$filtered_unit_values->EOF) {
                $filtered_unit_array[] = array(
                        'id' => $filtered_unit_values->fields['masterUnitID'],
                        'masterCountyID' => $filtered_unit_values->fields['masterCountyID'],
                        'masterAgencyID' => $filtered_unit_values->fields['masterAgencyID'],
                        'masterAgency' => $filtered_unit_values->fields['masterAgency'],
                        'masterUnitDescription' => $filtered_unit_values->fields['masterUnitDescription'],
                        'text' => $filtered_unit_values->fields['masterAgency'] . " " . $filtered_unit_values->fields['masterUnitDescription']);
                $filtered_unit_values->MoveNext();
                }; //end while
        return $filtered_unit_array;
    } //end filtered_unit_lookup

    function iems_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false) {
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
