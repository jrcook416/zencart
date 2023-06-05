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

    function iems_pull_down_menu($name, $values, $default = '', $parameters = '', $required = true) {
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

      $field = '<select rel="dropdown" class="col-md-5"';
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
			WHERE p.products_id = " . (int)$product_id . " AND q.source = '" . $_SESSION['IEMS']['agencyPricing'] . "'";
    $product = $db->Execute($sql, 1, true, 900);
    //Allow an observer to modify details
    $zco_notifier->notify('NOTIFY_GET_PRODUCT_DETAILS', $product_id, $product);
    return $product;
}

function iems_get_products_quantity_min_units_display($product_id, $include_break = true, $message_is_for_shopping_cart = false)
{
    $result = iems_get_product_details($product_id);

    if ($result->EOF) return '';

    $check_min = $result->fields['products_quantity_order_min'];
    $check_max = $result->fields['products_quantity_order_max'];
    $check_units = $result->fields['products_quantity_order_units'];
    $allows_mixed = $result->fields['products_quantity_mixed'];

    $the_min_units = '';

    if ($check_min != 1 or $check_units != 1) {
        if ($check_min != 1) {
            $the_min_units .= '<span class="qmin">' . PRODUCTS_QUANTITY_MIN_TEXT_LISTING . '&nbsp;' . $check_min . '</span>';
        }

        if ($check_units != 1) {
            $the_min_units .= '<span class="qunit">' . (zen_not_null($the_min_units) ? ' ' : '') . PRODUCTS_QUANTITY_UNIT_TEXT_LISTING . '&nbsp;' . $check_units . '</span>';
        }

        // don't check for mixed if no attributes
        $chk_mix = zen_has_product_attributes((int)$product_id) && $allows_mixed;
        if ($chk_mix === true) {
            $the_min_units .= '<span class="qmix">';
            if (($check_min > 0 || $check_units > 0)) {
                if ($include_break) {
                    $the_min_units .= '<br>';
                } else {
                    $the_min_units .= '&nbsp;&nbsp;';
                }
                $the_min_units .= ($message_is_for_shopping_cart == false ? TEXT_PRODUCTS_MIX_OFF : TEXT_PRODUCTS_MIX_OFF_SHOPPING_CART);

            } else {
                if ($include_break) {
                    $the_min_units .= '<br>';
                } else {
                    $the_min_units .= '&nbsp;&nbsp;';
                }
                $the_min_units .= ($message_is_for_shopping_cart == false ? TEXT_PRODUCTS_MIX_ON : TEXT_PRODUCTS_MIX_ON_SHOPPING_CART);
            }
            $the_min_units .= '</span>';
        }
    }

    if ($check_max > 0) {
        $the_min_units .= '<span class="qmax">';
        if ($include_break == true) {
            $the_min_units .= ($the_min_units != '' ? '<br>' : '');
        } else {
            $the_min_units .= ($the_min_units != '' ? '&nbsp;&nbsp;' : '');
        }
        $the_min_units .= PRODUCTS_QUANTITY_MAX_TEXT_LISTING . '&nbsp;' . $check_max;
        $the_min_units .= '</span>';
    }

    return $the_min_units;
}

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


