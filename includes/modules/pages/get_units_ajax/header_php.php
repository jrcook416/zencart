<?php
// Ensure this script is only executed within Zen Cart
defined('IS_ADMIN_FLAG') or die('Illegal Access');

// Disable standard HTML rendering for this AJAX page
$zco_notifier->notify('NOTIFY_HEADER_START_GET_UNITS_AJAX');

// Initialize the response structure
$response = array('success' => false, 'data' => array());

// Verify the Zen Cart security token and check if county_id is present
if (isset($_POST['securityToken']) && $_POST['securityToken'] == $_SESSION['securityToken'] && isset($_POST['county_id'])) {
    
    $county_id = (int)$_POST['county_id']; // Sanitize input as an integer

    if ($county_id > 0) {
        // Replace 'your_table_name' with your actual database table
        // Replace 'county_id_field', 'unit_id', and 'unit_name' with your actual column names
        $sql = "SELECT unit_id, unit_description 
                FROM iems_units 
                WHERE unit_countyID = :countyID 
                ORDER BY unit_description ASC";
                
        $sql = $db->bindVars($sql, ':countyID', $county_id, 'integer');
        $result = $db->Execute($sql);

        if ($result->RecordCount() > 0) {
            $response['success'] = true;
            while (!$result->EOF) {
                $response['data'][] = array(
                    'id' => $result->fields['unit_id'],
                    'name' => $result->fields['unit_description']
                );
                $result->MoveNext();
            }
        }
    }
}

// Clear any accidental whitespace or output buffers
ob_clean();

// Output JSON header and payload
header('Content-Type: application/json');
echo json_encode($response);

// Terminate script execution so Zen Cart does not append template HTML
require('includes/application_bottom.php');
exit();
