<?php

function test_function(){
	global $db;
	echo "This is my test function";
}


function unit_lookup() {
	global $db;
	global $unit_array;

	$unit_array = array();
	$unit_values = $db->Execute("select unit_description from `units` order by unit_description");

		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => $unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $unit_array; 
	}


function unit_lookup_filter() {
	global $db;
	global $unit_array;
	$filter = '49 IEMS';

	$unit_array = array();
	$unit_values = $db->Execute("select unit_description from `units` where unit_filter ='" .  $filter . "' order by unit_description");

		while (!$unit_values->EOF) {
			$unit_array[] = array('id' => $unit_values->fields['unit_description'], 'text' => 			$unit_values->fields['unit_description']);
			$unit_values->MoveNext();
			};
	return $unit_array; 
	}

?>
