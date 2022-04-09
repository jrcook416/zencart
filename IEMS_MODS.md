Zen Cart&reg; v1.5.7d built for IEMS Logistics.
---------------------
This is an upgrade/clean install built for Indianapolis EMS Logistics.  
The previous version, 1.5.6c, was retired from service on March 16, 2022.
This document was last updated with code changes on 2022-04-05.

Code Modifications  
---------------
All files where IEMS custom coding has been used should have been marked with the following comment line:
```
* //IEMS Custom Code - 2022-03-30// 
```

1. IEMS specific functions have been added to the extra_functions folders on core and Vector.  iems.php should be copied to both the core and Vector includes/functions/extra_functions folder.
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

* The iems_pull_down_menu() function will create a select based upon either unit_lookup() or unit_lookup_filtered(). 
2. The administrator/supply technician username (employee ID number) will show in updates to an order, just like in 1.5.6c.
3. Edit Orders 4.6.1 and Super Orders 5.0.0 code have been modified to allow for a select box for agency (company) and unit selection.
* Most of this modification is in /vector/includes/modules/edit_orders/eo_common_address_format.php. 
4. Javascript/AJAX code has been added to the /vector/customers.php file.
* The unit select will filter based upon the agency filter. (Requires Bootstrap-Select CDN link in the customers.php vector/javascript folder.
