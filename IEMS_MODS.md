Zen Cart&reg; v1.5.7d built for IEMS Logistics.
---------------------
This is an upgrade/clean install built for Indianapolis EMS Logistics.  
The previous version, 1.5.6c, was retired from service on March 16, 2022.
Please read all .md files prior to working on any part of the site.  
This document was last updated with code changes on 2022-04-09.
This document (IEMS_MODS.md) covers specific core code changes to the v1.5.7d custom build for Indianapolis EMS Logistics.   

Code Modifications  
---------------
All files where IEMS custom coding has been used should have been marked with the following comment line:
```
/**
 * @copyright Copyright 2022 Indianapolis EMS Logistics
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Jeremiah Cook 2022-03-30, modified for ZC v1.5.7d
 * //IEMS Custom Code - 2022-03-30//
 */
```
Additionally, each changed code block or line should be marked with a comment as well.  This will make upgrading and troubleshooting easier.
_**Go into details on upgrading and changing code.**_

1. IEMS specific functions have been added to the extra_functions folders on core and Vector.  Changes to iems.php should be copied to both the core and Vector includes/functions/extra_functions folder.  These function files are included with the repository.
* The unit_lookup() function will pull the current unit list, with the exception of the IEMS Reserve units, from the unit table, load it into an associative array, and return the array for use in a select.  It will load the unit description in the 'id' and 'text' fields.  The array can then be loaded into 
```
function unit_lookup() {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select unit_description from `units` where unit_description NOT LIKE '%reserve%' order by unit_description");

		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => $unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $unit_array; 
	}
```

* The unit_lookup_filtered() function will pull the current agency list from the unit table, load it into an associative array, and return the array for use in a select. **_For testing purposes, the $filter variable is passed directly in the function.  This should be changed in both files to pass the $filter variable from outside the function after testing is complete.  I may use the Javascript code established in /vector/customers.php to accomplish this in future versions of the code._** 

```
function unit_lookup_filtered() {
	global $db;
	global $unit_array;
	$filter = '49 IEMS';

	$unit_array = array();
	$unit_values = $db->Execute("select unit_description from `units` where unit_filter ='" .  $filter . "' order by unit_description");

		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => $unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $unit_array; 
	}
  ```

* The iems_pull_down_menu() function will create a select based upon either unit_lookup() or unit_lookup_filtered().  Ideally, you will pass the result from either unit lookup function (which should be set up as an associative array in a variable) to this function.
```
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

  if (strpos($parameters, 'id=') === false) {
    $field .= ' id="select-' . zen_output_string($name) . '"';
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
}

```

2. Edit Orders 4.6.1 and Super Orders 5.0.0 add some functionality to IEMS Zen Cart that was hard-coded in ZC 1.5.6c. The administrator/supply technician username (employee ID number) will show in updates to an order out of the box. 
3. Select boxes for agency (company) and unit selection are now part of the Edit Orders page.
4. ```
* Most of this modification is in /vector/includes/modules/edit_orders/eo_common_address_format.php. 
5. Javascript/AJAX code has been added to the /vector/customers.php file.
* The unit select will filter based upon the agency filter. (Requires Bootstrap-Select CDN link in the customers.php vector/javascript folder.
