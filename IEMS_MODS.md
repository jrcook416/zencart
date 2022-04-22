Zen Cart&reg; v1.5.7d built for IEMS Logistics.
---------------------
This is an upgrade/clean install built for Indianapolis EMS Logistics.  
The previous version, 1.5.6c, was retired from service on March 16, 2022.
Please read all .md files prior to working on any part of the site.  
This document was last updated with code changes on 2022-04-09.
This document (IEMS_MODS.md) covers specific core code changes to the v1.5.7d custom build for Indianapolis EMS Logistics.   

Code Modifications  
---------------
For business continuity and other purposes, the docBlock standard has been adopted for inline code documentation. <br>

All files where IEMS custom coding has been used should have been marked with the following docBlock at the top of the file:
```
/**
 * This is a file containing custom functions for the Indianapolis EMS implementation of Zen Cart.
 *
 *
 * Custom functions for Indianapolis EMS are defined in the /vector/includes/functions/extra_functions directory 
 * and the /includes/functions/extra_functions directory as per the Zen Cart coding standards.  This file should be
 * copied to each of those directories and maintained within Git version control.
 * For the sake of argument, this is the /vector/includes/functions/extra_functions version of this file.
 * As a standard, all code should be documented using phpDoc standards as laid out in the phpDoc manual and the 
 * IEMS documentation.
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 * 
 *
 * @package	admin
 * @category	Indianapolis EMS custom code
 * @link   	https://iemssupply.net
 * @author    	Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @copyright 	Copyright (c)2013-2022, Jeremiah Cook <jeremiah.cook@indianapolisems.org>
 * @license   	https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License 2
 * @version 	Jeremiah Cook 2022-04-11, modified for ZC v1.5.7d
 */
```
This is in addition to .md documentation written during the v1.5.7d clean install and upgrade in 2Q-2022.
<br>
**_NOTE: All IEMS Javascript and CSS revisions for v1.5.7d will be moving to separate files as per the Zen Cart coding standards. 
Core code modifications will still be documented inline by phpDoc convention._**
<br>
Individual functions should be documented using the following example: 
```
/**
 * Queries the unit table in the database and returns an array.
 *
 * Note: Variables existing inside of functions are tagged in the function docBlock where appropriate.  They will not show in the API documentation.
 *
 * @return 	mixed An associative array ($unit_array) holding unit descriptions and unit filters.
 * @var		array $db - the database specified in /vector/includes/configure.php
 * @var		array $unit_array - the associative array that we will load the unit list into.
 * @var		string $unit_values - the string that holds the MySQL query to pull all columns from the `units` table.
 */
```
Again, individual variables within the function are tagged with the @var tag, but they will not show in the API documentation.
<br>
Each changed code block or line should be marked with a comment as well.  This will make upgrading and troubleshooting easier.
The Zen Cart standard is that PHPDoc blocks be used at the beginning of the file and every ten lines.
<br>
_**Go into details on upgrading and changing code.**_

1. IEMS specific functions have been added to the extra_functions folders on core and Vector.  Changes to iems.php should be copied to both the core and Vector includes/functions/extra_functions folder.  These function files are included with the repository.
* The unit_lookup() function will pull the current unit list, with the exception of the IEMS Reserve units, from the unit table, load it into an associative array, and return the array for use in a select.  It will load the unit description in the 'id' and 'text' fields.  The array can then be loaded into 
```
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
* Most of this modification is in /vector/includes/modules/edit_orders/eo_common_address_format.php. 
4. Javascript/AJAX code has been added to the /vector/customers.php file.
* The unit select will filter based upon the agency filter. (Requires Bootstrap-Select CDN link in the customers.php vector/javascript folder.
5. The specific unit of measure for a product was added to the detailed catalog page in tpl_product_info_display.php.
6. Provider and Agency selection boxes have been added to the Edit Orders navigation bar.
7. Support for purchase orders has been enabled. 
