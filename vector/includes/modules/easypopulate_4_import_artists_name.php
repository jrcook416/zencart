<?php

if (isset($v_artists_name) && ($v_artists_name != '')) {
  $post_array = array(
    'artists_name' => array(
      'v_artists_name' => compact('v_artists_name'),
    ),
    'artists_image' => array(
      'v_artists_image' => compact('v_artists_image'),
    ),
  );
  
  $data_array = ep4_post_sanitize($post_array);
  extract($data_array, EXTR_OVERWRITE);
}
$v_artists_id = 0; // chadd - zencart uses artists_id = '0' for no assisgned artists
$art_name_str_len = isset($v_artists_name) && ($v_artists_name != '') ? (function_exists('mb_strlen') ? mb_strlen($v_artists_name) : strlen($v_artists_name)) : false;
if ($art_name_str_len === false) {
  $display_output .= sprintf(EASYPOPULATE_4_DISPLAY_RESULT_ARTISTS_NAME_EMPTY, $items[$filelayout[$chosen_key]], $chosen_key);
  return;
}

if ($art_name_str_len > $max_len['artists_name']) {
  $display_output .= sprintf(EASYPOPULATE_4_DISPLAY_RESULT_ARTISTS_NAME_LONG, $v_artists_name, $max_len['artists_name']);
  if (empty($max_len['artists_name_found']) || $max_len['artists_name_found'] < $art_name_str_len) {
    $max_len['artists_name_found'] = $art_name_str_len;

    if(EASYPOPULATE_4_CONFIG_AUTO_EXTEND_FIELD === 'false') {
      $ep_error_count++;
      unset($art_name_str_len);
      return;
    }
    $update_artists_name_sql = "ALTER TABLE " . TABLE_RECORD_ARTISTS . " CHANGE record_artists_name record_artists_name VARCHAR(" . (int)$art_name_str_len . ") NOT NULL DEFAULT '';";
    $update_artists_name = $db->Execute($update_artists_name_sql);

    zen_record_admin_activity('Extended table ' . TABLE_RECORD_COMPANY . ' field record_artists_name via EP4 from ' . zen_db_input($max_len['record_artists_name']) . ' to ' . zen_db_input($art_name_str_len) . '.', 'info');

    $max_len['artists_name'] = $art_name_str_len;
  }
}
unset($art_name_str_len);

$sql = "SELECT artists_id AS artistsID FROM " . TABLE_RECORD_ARTISTS . " WHERE artists_name = :artists_name: LIMIT 1";
$sql = $db->bindVars($sql, ':artists_name:', $v_artists_name, $zc_support_ignore_null);
$result = ep_4_query($sql);
unset($sql);


if ($row = $ep_4_fetch_array($result)) {
  unset($result);
  $v_artists_id = $row['artistsID']; // this id goes into the product_music_extra table, the other information is collected from the assignment of ${$key} = $items[$value]
  $sql = "UPDATE " . TABLE_RECORD_ARTISTS . " SET
    artists_image = :artists_image:,
    last_modified = CURRENT_TIMESTAMP
    WHERE artists_id = :artists_id:";
  $sql = $db->bindVars($sql, ':artists_image:', $v_artists_image, $zc_support_ignore_null);
  $sql = $db->bindVars($sql, ':artists_id:', $v_artists_id, 'integer');
  $result = ep_4_query($sql);
  unset($sql);
  if ($result) {
    zen_record_admin_activity('Updated record artist ' . (int) $v_artists_id . ' via EP4.', 'info');
  }
  unset($result);
  foreach ($langcode as $lang) {
    $l_id = $lang['id'];
    $l_id_code = $lang['code'];
    // if the column is not in the import file, then don't modify
    //  or update that particular language's value.  This way
    //  only the columns desired to be updated are modified, not
    //  all columns and thus require on any update to have all
    //  columns present even those not being updated.
    if (!ep4_field_in_file('v_artists_url_' . $l_id) && !ep4_field_in_file('v_artists_url_' . $l_id_code)) {
      unset($l_id);
      unset($l_id_code);
      unset($lang);
      continue;
    }
    $sql = "UPDATE " . TABLE_RECORD_ARTISTS_INFO . " SET
      artists_url = :artists_url:
      WHERE artists_id = :artists_id: AND languages_id = :languages_id:";
    // Need to admin sanitize the and handle both languages id and  code.

    $post_array = array(
      'artists_url' => array(
        'lang' => array(
          'lid' => array($l_id => array('v_artists_url_' . $l_id => ep4_field_in_file('v_artists_url_' . $l_id) ? $items[$filelayout['v_artists_url_' . $l_id]] : ''),),
          'code' => array($l_id_code => array('v_artists_url_' . $l_id_code => ep4_field_in_file('v_artists_url_' . $l_id_code) ? $items[$filelayout['v_artists_url_' . $l_id_code]] : ''),),
          'var' => array('thisartistsurl' => $v_artists_id), //compact('v_categories_name'),
        ),
      ),
    );

    $data_array = ep4_post_sanitize($post_array);
      
    extract($data_array, EXTR_OVERWRITE);


    $sql = $db->bindVars($sql, ':artists_id:', $v_artists_id, 'integer');
    $sql = $db->bindVars($sql, ':artists_url:', $thisartistsurl[$l_id], $zc_support_ignore_null);
    $sql = $db->bindVars($sql, ':languages_id:', $l_id, 'integer');
    $result = ep_4_query($sql);
    unset($sql);
    if ($result) {
      zen_record_admin_activity('Updated record artist info ' . (int) $v_artists_id . ' via EP4.', 'info');
    }
    unset($l_id);
    unset($result);
  }
  unset($lang);
  return;
} // It is set to autoincrement, do not need to fetch max id
unset($result);
$sql = "INSERT INTO " . TABLE_RECORD_ARTISTS . " (artists_name, artists_image, date_added)
  VALUES (:artists_name:, :artists_image:, CURRENT_TIMESTAMP)";

// Need to admin sanitize the and handle both languages id and  code.


$sql = $db->bindVars($sql, ':artists_name:', $v_artists_name, $zc_support_ignore_null);
$sql = $db->bindVars($sql, ':artists_image:', $v_artists_image, $zc_support_ignore_null);
$result = ep_4_query($sql);
unset($sql);

$v_artists_id = $ep_4_insert_id(($ep_uses_mysqli ? $db->link : null)); // id is auto_increment, so can use this function

if ($result) {
  zen_record_admin_activity('Inserted record artist ' . zen_db_input(ep_4_curly_quotes($v_artists_name)) . ' via EP4.', 'info');
}
unset($result);

foreach ($langcode as $lang) {
  $l_id = $lang['id'];
  $l_id_code = $lang['code'];
  // If the artists_url column for this language was not in the file,
  //  then do not modify the setting... But, also make sure
  //  using the correct "check" mc12345678 2015-12-30
  //  $filelayout chosen as it is to return an array represeting
  //  the position in the file that is translated to the data
  //  at that position.  For an insert (ie. new record), if
  //  all of the data is not provided, then will populate with
  //  the data of the "first" language (which should be included)
  //  if the particular artists_url is provided then that is used.
  $sql = "INSERT INTO " . TABLE_RECORD_ARTISTS_INFO . " (artists_id, languages_id, artists_url)
    VALUES (:artists_id:, :languages_id:, :artists_url:)"; // seems we are skipping manufacturers url
    
  $post_array = array(
    'artists_url' => array(
      'lang' => array(
        'lid' => array($l_id => array('v_artists_url_' . $l_id => ep4_field_in_file('v_artists_url_' . $l_id) ? $items[$filelayout['v_artists_url_' . $l_id]] : ''),),
        'code' => array($l_id_code => array('v_artists_url_' . $l_id_code => ep4_field_in_file('v_artists_url_' . $l_id_code) ? $items[$filelayout['v_artists_url_' . $l_id_code]] : ''),),
        'var' => array('thisartistsurl' => $v_artists_id), //compact('v_categories_name'),
      ),
    ),
  );

  $data_array = ep4_post_sanitize($post_array);
  
  extract($data_array, EXTR_OVERWRITE);


  $sql = $db->bindVars($sql, ':artists_id:', $v_artists_id, 'integer');
  $sql = $db->bindVars($sql, ':languages_id:', $l_id, 'integer');
  $sql = $db->bindVars($sql, ':artists_url:', $thisartistsurl[$l_id], $zc_support_ignore_null);
  $result = ep_4_query($sql);
  unset($post_array);
  unset($data_array);
  unset($sql);
  if ($result) {
    zen_record_admin_activity('Inserted record artists info ' . (int) $v_artists_id . ' via EP4.', 'info');
  }
  unset($l_id);
  unset($result);
}
unset($lang);
