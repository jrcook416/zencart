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
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.
 * They will not show in the API documentation.
 *
 *
 * @package		IEMSCustomFiles
 * @subpackage	Vector
 * @category	Indianapolis EMS custom code
 * @link   		<https://www.iemssupply.net>
 * @author    	Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @copyright   Copyright 2003-2022 Zen Cart Development Team
 * @copyright   Portions Copyright 2003 osCommerce
 * @copyright 	Copyright (c)2013-2023, Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @license     http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version 	Jeremiah Cook 2023-02-22, modified for ZC v1.5.8
 */

 /*
 * function county_lookup()
 *
 * This function drives the county selector in vector/customers.php.
 * It will pull the `countyID`, `countyCode`, and `countyName` records and load them into an associative array
 * that will in turn load into a county selector with a primary ID field of `countyID` and a text field containing the
 * `countyCode` concatenated with the `countyName` for display.
 *
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

 /*
 * function agency_lookup()
 *
 * This function drives the agency selector in vector/customers.php.
 * It will pull the `masterAgencyID`, `masterAgency`, `masterCountyID`, and `masterAgencyDescription` records
 * and load them into an associative array that will in turn load into an agency selector with a primary ID field
 * of ` masterAgencyID` and a text field containing the `masterAgency` concatenated with the `masterAgencyDescription` for display.
 *
 */
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


 /*
 * function unit_lookup()
 *
 * This function drives the agency selector in vector/customers.php.
 * It will pull the `masterUnitID`, `masterCountyID`, `masterAgencyID`, `masterAgency`, and `masterUnitDescription` records
 * and load them into an associative array that will in turn load into an agency selector with a primary ID field
 * of ` masterUnitID` and a text field containing the `masterAgency` concatenated with the `masterUnitDescription` for display.
 *
 */

function unit_lookup() {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select agency.masterCountyID as masterCountyID, agency.masterAgency as masterAgency,
									agency.masterAgencyDescription as masterAgencyDescription, unit.masterUnitID as masterUnitID,
									unit.masterAgencyID as masterAgencyID,
									concat(agency.masterCountyID, ' ' , agency.masterAgency, ' ' , unit.masterUnitDescription) as `unitDescription`
									from iems_units unit left join iems_agencies agency on agency.masterAgencyID = unit.masterAgencyID;");
		while (!$unit_values->EOF) {
			$unit_array[] = array(
					'id' => $unit_values->fields['masterUnitID'],
					'masterAgencyID' => $unit_values->fields['masterAgencyID'],
					'masterUnitDescription' => $unit_values->fields['unitDescription'],
					'text' => $unit_values->fields['unitDescription']);
			$unit_values->MoveNext();
			}; //end while
	return $unit_array;
	} //end unit_lookup


 /*
 * function iems_pull_down_menu($name, $values, $default = '', $parameters = '', $required = false)
 *
 * This function is a carbon-copy of the zen_pull_down_menu function EXCEPT adding the 'select-' identifier to the field tag,
 * which is specific for the bootstrap-select functionality within the select elements.
 *
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


 /*
 * function uom_lookup()
 *
 * This function pulls a list of units of measure from a unit of measure table. It is currently not used.
 *
 */
function uom_lookup() {
	global $db;
	global $uom_array;
	$uom_array = array();
	$uom_values = $db->Execute("select uom_id, uom from `iems_uom` ");

		while (!$uom_values->EOF) {
			$uom_array[] = array('id' => $uom_values->fields['uom'], 'text' => $uom_values->fields['uom']);
			$uom_values->MoveNext();
			};
	return $uom_array;
	} //end uom_array


 /*
 * function unit_name_lookup()
 *
 * This function drives the agency selector in vector/customers.php.
 * It will pull the `masterUnitID`, `masterCountyID`, `masterAgencyID`, `masterAgency`, and `masterUnitDescription` records
 * and load them into an associative array that will in turn load into an agency selector with a primary ID field
 * of ` masterUnitID` and a text field containing the `masterAgency` concatenated with the `masterUnitDescription` for display.
 *
 */
function unit_name_lookup($unitCode){
    global $db;
    global $unitName;
    $unit_values = $db->Execute("select agency.masterCountyID as masterCountyID, agency.masterAgency as masterAgency, agency.masterAgencyDescription as masterAgencyDescription,
									unit.masterUnitID as masterUnitID, unit.masterAgencyID as masterAgencyID,
									concat(agency.masterCountyID, ' ' , agency.masterAgency, ' ' , agency.masterAgencyDescription, ' ' , unit.masterUnitDescription) as `unitDescription`
									from iems_units unit left join iems_agencies agency on agency.masterAgencyID = unit.masterAgencyID
									where masterUnitID = '" . $unitCode . "' LIMIT 1");
    while (!$unit_values->EOF){
        $countyID = $unit_values->fields['masterCountyID'];
        $agencyID = $unit_values->fields['masterAgency'];
		$agencyName = $unit_values->fields['masterAgencyDescription'];
        $unitDesc = $unit_values->fields['unitDescription'];
        $unitName = $unitDesc;
        $unit_values->MoveNext();
    };
    return $unitName;
} //end unit_code_lookup

function agency_name_lookup($agencyCode){
    global $db;
    global $agencyName;
    $agency_values = $db->Execute("select masterAgencyID, masterAgency, masterCountyID, masterAgencyDescription from iems_agencies where masterAgencyID = '" . $agencyCode . "' LIMIT 1");
    while (!$agency_values->EOF){
        $countyID = $agency_values->fields['masterCountyID'];
        $agencyID = $agency_values->fields['masterAgency'];
        $agencyDesc = $agency_values->fields['masterAgencyDescription'];
        $agencyName = $countyID . " " . $agencyID . " " . $agencyDesc;
        $agency_values->MoveNext();
    };
    return $agencyName;
} //end unit_code_lookup

function filtered_unit_lookup($agency) {
    global $db;
    global $filtered_unit_array;

    $filtered_unit_array = array();
    $filtered_unit_values = $db->Execute("select agency.masterCountyID as masterCountyID, agency.masterAgency as masterAgency, agency.masterAgencyDescription as masterAgencyDescription,
									unit.masterUnitID as masterUnitID, unit.masterAgencyID as masterAgencyID,
									concat(agency.masterCountyID, ' ' , agency.masterAgency, ' ' , agency.masterAgencyDescription, ' ' , unit.masterUnitDescription) as `unitDescription`
									from iems_units unit left join iems_agencies agency on agency.masterAgencyID = unit.masterAgencyID
									where unit.masterAgencyID = '" . $agency . "'");
    while (!$filtered_unit_values->EOF) {
        $filtered_unit_array[] = array(
            'id' => $filtered_unit_values->fields['masterUnitID'],
            'masterAgencyID' => $filtered_unit_values->fields['masterAgencyID'],
            'masterUnitDescription' => $filtered_unit_values->fields['unitDescription'],
            'text' => $filtered_unit_values->fields['unitDescription']);
        $filtered_unit_values->MoveNext();
    }; //end while
    return $filtered_unit_array;
} //end filtered_unit_lookup


function iems_get_product_details($product_id, $language_id = null)
{
    global $db, $zco_notifier;

    if ($language_id === null) $language_id = $_SESSION['languages_id'];

     $sql = "SELECT p.products_id, p.products_type, p.products_quantity, p.products_model, p.products_image,
			p.products_price, p.products_uom, p.products_virtual, p.products_date_added, p.products_last_modified,
			p.products_date_available, p.products_weight, q.products_status AS products_status,
			p.products_tax_class_id, p.manufacturers_id, p.products_ordered,
			q.products_quantity_order_min AS products_quantity_order_min,
			q.products_quantity_order_units AS products_quantity_order_units, p.products_priced_by_attribute,
			p.product_is_free, p.product_is_call, p.products_quantity_mixed, p.product_is_always_free_shipping,
			p.products_qty_box_status, q.products_quantity_order_max AS products_quantity_order_max,
			p.products_sort_order, p.products_discount_type, p.products_discount_type_from, p.products_price_sorter,
			p.master_categories_id, p.products_mixed_discount_quantity, p.metatags_title_status, p.metatags_products_name_status,
			p.metatags_model_status, p. metatags_price_status, p.metatags_title_tagline_status, pd.*, pt.allow_add_to_cart, pt.type_handler
            FROM " . TABLE_PRODUCTS . " p
            LEFT JOIN " . TABLE_PRODUCT_TYPES . " pt ON (p.products_type = pt.type_id)
            LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id AND pd.language_id = " . (int)$language_id . ")
			LEFT JOIN " . TABLE_QUANTITY . " q ON (p.products_id = q.products_id)
			WHERE p.products_id = " . (int)$product_id;
    $product = $db->Execute($sql, 1, true, 900);
    //Allow an observer to modify details
    $zco_notifier->notify('NOTIFY_GET_PRODUCT_DETAILS', $product_id, $product);
    return $product;
}
	function iems_set_product_status($product_id, $status)
{
    global $db;
    $db->Execute("UPDATE iems_quantities
                SET products_status = " . (int)$status . "
                WHERE products_id = " . (int)$product_id . "
                AND source = 'IEMS' ");
}

function ifd_set_product_status($product_id, $status)
{
    global $db;
    $db->Execute("UPDATE iems_quantities
                SET products_status = " . (int)$status . "
                WHERE products_id = " . (int)$product_id . "
                AND source = 'IFD' ");
}

function ext_set_product_status($product_id, $status)
{
    global $db;
    $db->Execute("UPDATE iems_quantities
                SET products_status = " . (int)$status . "
                WHERE products_id = " . (int)$product_id . "
                AND source = 'EXT' ");
}

function wayne_set_product_status($product_id, $status)
{
    global $db;
    $db->Execute("UPDATE iems_quantities
                SET products_status = " . (int)$status . "
                WHERE products_id = " . (int)$product_id . "
                AND source = 'WAYNE' ");
}
