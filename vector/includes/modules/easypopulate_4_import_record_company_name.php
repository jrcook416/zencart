<?php
/**
 * Edited 2019-07-01 to try to support language_code override and use.
 * @ author mc12345678 of http://mc12345678.com
 **/


if (isset($v_record_company_name) && ($v_record_company_name != '')) {
  $post_array = array(
    'record_company_name' => array(
      'v_record_company_name' => compact('v_record_company_name'),
    ),
    'record_company_image' => array(
      'v_record_company_image' => compact('v_record_company_image'),
    ),
  );
  
  $data_array = ep4_post_sanitize($post_array);
  extract($data_array, EXTR_OVERWRITE);


}
$record_company_name_str_len = isset($v_record_company_name) && ($v_record_company_name != '') ? (function_exists('mb_strlen') ? mb_strlen($v_record_company_name) : strlen($v_record_company_name)) : false;
$v_record_company_id = 0; // chadd - zencart uses artists_id = '0' for no assisgned artists

if ($record_company_name_str_len === false) {
  $display_output .= sprintf(EASYPOPULATE_4_DISPLAY_RESULT_RECORD_COMPANY_NAME_EMPTY, $items[$filelayout[$chosen_key]], $chosen_key);
  return;
}

if ($record_company_name_str_len > $max_len['record_company_name']) {
  $display_output .= sprintf(EASYPOPULATE_4_DISPLAY_RESULT_RECORD_COMPANY_NAME_LONG, $v_record_company_name, $max_len['record_company_name']);
  if (empty($max_len['record_company_name_found']) || $max_len['record_company_name_found'] < $record_company_name_str_len) {
    $max_len['record_company_name_found'] = $record_company_name_str_len;
    if (EASYPOPULATE_4_CONFIG_AUTO_EXTEND_FIELD === 'false') {
      $ep_error_count++;
      unset($record_company_name_str_len);
      return;
    }
    $update_record_company_name_sql = "ALTER TABLE " . TABLE_RECORD_COMPANY . " CHANGE record_company_name record_company_name VARCHAR(" . (int)$record_company_name_str_len . ") NOT NULL DEFAULT '';";
    $update_record_company_name = $db->Execute($update_record_company_name_sql);

    zen_record_admin_activity('Extended table ' . TABLE_RECORD_COMPANY . ' field record_company_name via EP4 from ' . zen_db_input($max_len['record_company_name']) . ' to ' . zen_db_input($record_company_name_str_len) . '.', 'info');

    $max_len['record_company_name'] = $record_company_name_str_len;
  }
}
unset($record_company_name_str_len);
$sql = "SELECT record_company_id AS record_companyID FROM " . TABLE_RECORD_COMPANY . " WHERE record_company_name = :record_company_name: LIMIT 1";
$sql = $db->bindVars($sql, ':record_company_name:', ep_4_curly_quotes($v_record_company_name), $zc_support_ignore_null);
$result = ep_4_query($sql);
if ($row = $ep_4_fetch_array($result)) {
  $v_record_company_id = $row['record_companyID']; // this id goes into the product_music_extra table
  $sql = "UPDATE " . TABLE_RECORD_COMPANY . " SET
    record_company_image = :record_company_image:,
    last_modified = CURRENT_TIMESTAMP
    WHERE record_company_id = :record_company_id:";
  $sql = $db->bindVars($sql, ':record_company_image:', $v_record_company_image, $zc_support_ignore_null);
  $sql = $db->bindVars($sql, ':record_company_id:', $v_record_company_id, 'integer');
  $result = ep_4_query($sql);
  if ($result) {
    zen_record_admin_activity('Updated record company ' . (int) $v_record_company_id . ' via EP4.', 'info');
  }
  $v_record_company_url = array();
  
  foreach ($langcode as $lang) {
    $l_id = $lang['id'];
    $l_id_code = $lang['code'];
    if (!isset($filelayout['v_record_company_url_' . $l_id]) && !isset($filelayout['v_record_company_url_' . $l_id_code])) {
      unset($l_id);
      unset($l_id_code);
      unset($lang);
      continue;
    }
    
    if (ep4_field_in_file('v_record_company_url_' . $l_id) && EASYPOPULATE_4_CONFIG_IMPORT_OVERRIDE != 'language_code_only') {
      $v_record_company_url_store = $v_record_company_url[$l_id] = $items[$filelayout['v_record_company_url_' . $l_id]];
    }
    if (ep4_field_in_file('v_record_company_url_' . $l_id_code) && EASYPOPULATE_4_CONFIG_IMPORT_OVERRIDE != 'language_id_only') {
      $v_record_company_url[$l_id_code] = $items[$filelayout['v_record_company_url_' . $l_id_code]];
      if (!ep4_field_in_file('v_record_company_url_' . $l_id) || EASYPOPULATE_4_CONFIG_IMPORT_OVERRIDE != 'language_id') {
        $v_record_company_url_store = $v_record_company_url[$l_id_code];
      }
      // Prioritize that if the $lang_id is also present for this language that this content rules/overrides.
    }
    
    if (isset($v_record_company_url_store) && ($v_record_company_url_store != '')) {

      $post_array = array(
        'record_company_url' => array(
          'v_record_company_url' => compact('v_record_company_url_store'),
        ),
      );

      $data_array = ep4_post_sanitize($post_array);
      
      extract($data_array, EXTR_OVERWRITE);
      
    }
    
/*                // Ensure that $lang_id version of variable is populated for other uses.
                if (!ep4_field_in_file('v_record_company_url_' . $l_id) && ep4_field_in_file('v_record_company_url_' . $l_id_code)) {
                  $v_record_company_url[$l_id] = $v_record_company_url[$l_id_code];
                }
*/                
    $sql = "UPDATE " . TABLE_RECORD_COMPANY_INFO . " SET
      record_company_url = :record_company_url:
      WHERE record_company_id = :record_company_id: AND languages_id = :languages_id:";
    $sql = $db->bindVars($sql, ':record_company_url:', $v_record_company_url_store, $zc_support_ignore_null);
    $sql = $db->bindVars($sql, ':record_company_id:', $v_record_company_id, 'integer');
    $sql = $db->bindVars($sql, ':languages_id:', $l_id, 'integer');
    $result = ep_4_query($sql);
    if ($result) {
      zen_record_admin_activity('Updated record company info ' . (int) $v_record_company_id . ' via EP4.', 'info');
    }
    unset($post_array);
    unset($data_array);
    unset($sql);
    unset($result);
    unset($l_id);
    unset($l_id_code);
  }
  unset($lang);
  return;
} /*else {*/ // It is set to autoincrement, do not need to fetch max id
// When inserting a new record company, need to also ensure insertion
//   of matching record company information records for each language.
//   This at least matches ZC operation.
$sql = "INSERT INTO " . TABLE_RECORD_COMPANY . " (record_company_name, record_company_image, date_added)
  VALUES (:record_company_name:, :record_company_image:, CURRENT_TIMESTAMP)";
$sql = $db->bindVars($sql, ':record_company_name:', ep_4_curly_quotes($v_record_company_name), $zc_support_ignore_null);
$sql = $db->bindVars($sql, ':record_company_image:', $v_record_company_image, $zc_support_ignore_null);
$result = ep_4_query($sql);

$v_record_company_id = $ep_4_insert_id(($ep_uses_mysqli ? $db->link : null)); // id is auto_increment, so can use this function

if ($result) {
  zen_record_admin_activity('Inserted record company ' . zen_db_input(ep_4_curly_quotes($v_record_company_name)) . ' via EP4.', 'info');
}

$v_record_company_url = array();

foreach ($langcode as $lang) {
  $l_id = $lang['id'];
  $l_id_code = $lang['code'];
  
  if (!isset($filelayout['v_record_company_url_' . $l_id]) && !isset($filelayout['v_record_company_url_' . $l_id_code])) {
    unset($l_id);
    unset($l_id_code);
    unset($lang);
    continue;
  }
  
  $v_record_company_url = array();
  
  if (ep4_field_in_file('v_record_company_url_' . $l_id) && EASYPOPULATE_4_CONFIG_IMPORT_OVERRIDE != 'language_code_only') {
    $v_record_company_url_store = $v_record_company_url[$l_id] = $items[$filelayout['v_record_company_url_' . $l_id]];
  }
  if (ep4_field_in_file('v_record_company_url_' . $l_id_code) && EASYPOPULATE_4_CONFIG_IMPORT_OVERRIDE != 'language_id_only') {
    $v_record_company_url[$l_id_code] = $items[$filelayout['v_record_company_url_' . $l_id_code]];
    if (!ep4_field_in_file('v_record_company_url_' . $l_id) || EASYPOPULATE_4_CONFIG_IMPORT_OVERRIDE != 'language_id') {
      $v_record_company_url_store = $v_record_company_url[$l_id_code];
    }
    // Prioritize that if the $lang_id is also present for this language that this content rules/overrides.
  }
  
  $post_array = array(
    'record_company_url' => array(
      'v_record_company_url' => compact('v_record_company_url_store'),
    ),
  );

  $data_array = ep4_post_sanitize($post_array);
  
  extract($data_array, EXTR_OVERWRITE);
  
  $sql = "INSERT INTO " . TABLE_RECORD_COMPANY_INFO . " (record_company_id, languages_id, record_company_url)
    VALUES (:record_company_id:, :languages_id:, :record_company_url:)"; // seems we are skipping manufacturers url
  $sql = $db->bindVars($sql, ':record_company_id:', $v_record_company_id, 'integer');
  $sql = $db->bindVars($sql, ':languages_id:', $l_id, 'integer');
  $sql = $db->bindVars($sql, ':record_company_url:',  $v_record_company_url_store, $zc_support_ignore_null);
  $result = ep_4_query($sql);
  if ($result) {
    zen_record_admin_activity('Inserted record company info ' . (int) $v_record_company_id . ' via EP4.', 'info');
  }
  unset($post_array);
  unset($data_array);
  unset($sql);
  unset($result);
  
}
unset($lang);
