Code Modifications  
---------------
1. IEMS specific functions have been added to the extra_functions folders on Core and Vector. 
* The unit_lookup() function will pull the current unit list from the unit table, load it into an associative array, and return the array for use in a select.  It will load the unit description in the 'id' and 'text' fields and the unit filter in the 'agency_filter' field. 
* The unit_lookup_filtered() function will pull the current agency list from the unit table, load it into an associative array, and return the array for use in a select. (Deprecated on 2022-04-05 - a filtering select was created using Javascript and PHP in the vector/customers.php file.)
* The iems_pull_down_menu() function will create a select based upon either unit_lookup() or unit_lookup_filtered(). 
2. The administrator/supply technician username (employee ID number) will show in updates to an order, just like in 1.5.6c.
3. Edit Orders 4.6.1 and Super Orders 5.0.0 code have been modified to allow for a select box for agency (company) and unit selection.
* Most of this modification is in /vector/includes/modules/edit_orders/eo_common_address_format.php. 
4. Javascript/AJAX code has been added to the /vector/customers.php file.
* The unit select will filter based upon the agency filter. (Requires Bootstrap-Select CDN link in the customers.php vector/javascript folder.
