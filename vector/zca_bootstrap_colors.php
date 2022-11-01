<?php
/**
 * @package admin
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Fri Feb 26 00:25:51 2016 -0500 Modified in v1.5.5 $
 *
 * BOOTSTRAP v3.1.2
 */
require('includes/application_top.php');

$sqlGroup = "SELECT configuration_group_id
             FROM " . TABLE_CONFIGURATION_GROUP . "
             WHERE configuration_group_title = 'ZCA Bootstrap Colors'";
$groupID = $db->Execute($sqlGroup);
// Without a valid config group present, it means the ZCA Bootstrap module isn't installed/configured yet/anymore.
if ($groupID->EOF) {
  $messageStack->add_session(MISSING_CONFIGURATION, 'error');
  zen_redirect(zen_href_link(FILENAME_DEFAULT));
}
$gID = $groupID->fields['configuration_group_id'];

$action = (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {

// BOF upload CSV file
    case 'uploadcsv':
      $file_contents = '';
      $color_list = [];
      $fail_count = 0;
      $line_count = 0;
      if (!empty($_FILES['csv_file']['tmp_name'])) {
        $filename = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($filename, "r")) !== false) {
          while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $line_count++;
            if (count($data) < 2) {
              $string = 'Insufficient columns in line ' . $line_count;
              error_log(print_r($string, true) . "\n", 3, DIR_FS_CATALOG . '/logs/zca_bootstrap_colors.log');
              $fail_count++;
              continue;
            }
            if ($line_count === 1 && ($data[0] != CSV_HEADER_KEY || $data[1] != CSV_HEADER_VALUE)) {
              $errormsg = 'Incorrect column headers in line ' . $line_count;
              error_log(print_r($errormsg, true) . "\n", 3, DIR_FS_CATALOG . '/logs/zca_bootstrap_colors.log');
              $fail_count++;
              continue;
            }
            $color_list[] = [
              'configuration_key' => $data[0],
              'configuration_value' => $data[1],
            ];
          }
          fclose($handle);
        }
      }

      if (empty($fail_count) && !empty($color_list)) {
        $success_count = 0;
        $fail_count = 0;
        $line_count = 0;
        foreach ($color_list as $color) {
          $line_count++;
          if ($line_count == 1) {           // ignore header line
            continue;
          }
          $configuration_key = zen_db_input($color['configuration_key']);
          $configuration_value = zen_db_input($color['configuration_value']);

          $color_query = $db->Execute("SELECT * FROM " . TABLE_CONFIGURATION . "
                                       WHERE configuration_group_id=" . (int)$gID . " AND configuration_key='" . $configuration_key . "'");
          if ($color_query->RecordCount() != 1) {
            if ($color_query->EOF) {
              $string = 'Error in line ' . $line_count . ' - no matching key ' . $configuration_key;
              error_log(print_r($string, true) . "\n", 3, DIR_FS_CATALOG . '/logs/zca_bootstrap_colors.log');
            }
            $fail_count++;
            continue;
          }
          $db->Execute("UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = '" . $configuration_value . "',
                            last_modified = now()
                        WHERE configuration_group_id=" . (int)$gID . " AND configuration_key='" . $configuration_key . "'");
          $success_count++;
        }
        if (empty($fail_count)) {
          $messageStack->add(UPLOAD_SUCCESS . sprintf(UPLOAD_FILE_PROCESSED_ALL_OK, $success_count), 'success');
        } else {
          $messageStack->add(UPLOAD_WARNING . sprintf(UPLOAD_FILE_PROCESSED_SOME_OK, $success_count, $success_count + $fail_count), 'caution');
        }
      } else {
        if ($fail_count == 0) {
          $messageStack->add(UPLOAD_FAILED . NO_CSV_FILE, 'error');
        } else {
          $messageStack->add(UPLOAD_FAILED . CSV_FILE_MALFORMED, 'error');
        }
      }
      break;
// EOF upload SQL file

// BOF download CSV file
    case 'downloadcsv':
      $filename = 'zca_bootstrap_colors_' . date('Ymd_His') . '.csv';
      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename=' . $filename);
      $out = fopen('php://output', 'w');
      fputcsv($out, [CSV_HEADER_KEY, CSV_HEADER_VALUE, CSV_HEADER_TITLE]);

      $configuration = $db->Execute("SELECT configuration_value, configuration_key, configuration_title
                                     FROM " . TABLE_CONFIGURATION . "
                                     WHERE configuration_group_id = " . (int)$gID . "
                                     ORDER BY sort_order");
      foreach ($configuration as $item) {
        fputcsv($out, [$item['configuration_key'], $item['configuration_value'], $item['configuration_title']]);
      }

      fclose($out);
      die();
      break;
// EOF download SQL file

    case 'save':
      $cID = zen_db_prepare_input($_GET['cID']);

      $configuration_value = zen_db_prepare_input($_POST['configuration_value']);

      $db->Execute("UPDATE " . TABLE_CONFIGURATION . "
                    SET configuration_value = '" . zen_db_input($configuration_value) . "',
                        last_modified = now()
                    WHERE configuration_id = " . (int)$cID);

      zen_redirect(zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . (int)$cID));
      break;
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
  </head>
  <body>
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->

    <!-- body //-->

    <div class="container-fluid">
      <h1><?php echo HEADING_TITLE; ?></h1>
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
          <table class="table table-hover">
            <thead>
              <tr class="dataTableHeadingRow">
                <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_CONFIGURATION_TITLE; ?></th>
                <th class="dataTableHeadingContent"><?php echo TABLE_HEADING_CONFIGURATION_VALUE; ?></th>
                <th class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_ACTION; ?></th>
              </tr>
            </thead>
            <tbody>
                <?php
                $configuration = $db->Execute("SELECT configuration_id, configuration_title, configuration_value, configuration_key, use_function
                                               FROM " . TABLE_CONFIGURATION . "
                                               WHERE configuration_group_id = " . (int)$gID . "
                                               ORDER BY sort_order");

                foreach ($configuration as $item) {
                  if (!empty($item['use_function'])) {
                    $use_function = $item['use_function'];
                    if (preg_match('/->/', $use_function)) {
                      $class_method = explode('->', $use_function);
                      if (!is_object(${$class_method[0]})) {

                        include(DIR_WS_CLASSES . $class_method[0] . '.php');
                        ${$class_method[0]} = new $class_method[0]();
                      }
                      $cfgValue = zen_call_function($class_method[1], $item['configuration_value'], ${$class_method[0]});
                    } else {
                      $cfgValue = zen_call_function($use_function, $item['configuration_value']);
                    }
                  } else {
                    $cfgValue = $item['configuration_value'];
                  }

                  if ((!isset($_GET['cID']) || (isset($_GET['cID']) && ($_GET['cID'] == $item['configuration_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
                    $cfg_extra = $db->Execute("SELECT configuration_key, configuration_description, date_added,
                                                      last_modified, use_function, set_function
                                               FROM " . TABLE_CONFIGURATION . "
                                               WHERE configuration_id = " . (int)$item['configuration_id']);
                    $cInfo_array = array_merge($item, $cfg_extra->fields);
                    $cInfo = new objectInfo($cInfo_array);
                  }

                  if ((isset($cInfo) && is_object($cInfo)) && ($item['configuration_id'] == $cInfo->configuration_id)) {
                    ?>
                  <tr id="defaultSelected" class="dataTableRowSelected" onclick="document.location.href = '<?php echo zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . (int)$cInfo->configuration_id . '&action=edit'); ?>'">
                      <?php
                    } else {
                      ?>
                  <tr class="dataTableRow" onclick="document.location.href = '<?php echo zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . (int)$item['configuration_id'] . '&action=edit'); ?>'">
                      <?php
                    }
                    ?>
                  <td class="dataTableContent"><?php echo $item['configuration_title']; ?></td>
                  <td class="dataTableContent">
                    <i class="fa fa-square fa-border" aria-hidden="true" style="font-size: 1.35em;margin-right:.5em;background-color:#ffffff;color:<?php echo htmlspecialchars($cfgValue, ENT_COMPAT, CHARSET, TRUE); ?>;"></i>
                    <?php echo htmlspecialchars($cfgValue, ENT_COMPAT, CHARSET, TRUE); ?>
                  </td>
                  <td class="dataTableContent text-right">
                      <?php
                      if ((isset($cInfo) && is_object($cInfo)) && ($item['configuration_id'] == $cInfo->configuration_id)) {
                        echo zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', '');
                      } else {
                        echo '<a href="' . zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . (int)$item['configuration_id']) . '" id="link_' . $item['configuration_key'] . '">' . zen_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>';
                      }
                      ?>
                  </td>
                </tr>
                <?php
              }
              ?>
            </tbody>
          </table>
<?php
/* BOF CSV file */
if (!empty($gID)) {
?>
          <div class="row">
            <?php echo zen_draw_form('upload_csv', FILENAME_ZCA_BOOTSTRAP_COLORS, 'action=uploadcsv', 'post', 'enctype="multipart/form-data" class="form-horizontal"'); ?>
              <div class="form-group">
                <?php echo zen_draw_label(TEXT_QUERY_FILENAME, 'csv_file', 'class="control-label col-sm-3"'); ?>
                <div class="col-sm-6"><?php echo zen_draw_file_field('csv_file', '', 'class="form-control" id="csv_file"'); ?></div>
                <div class="col-sm-3 text-right"><button type="submit" class="btn btn-primary"><?php echo BUTTON_UPLOAD_CSV; ?></button></div>
              </div>
            <?php echo '</form>'; ?>
          </div>
          <div class="row text-right">
            <a class="btn btn-primary" role="button" href="<?php echo zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'action=downloadcsv', 'SSL') ?>"><?php echo BUTTON_DOWNLOAD_CSV; ?></a>
          </div>
<?php
}
/* EOF CSV file */
?>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
          <?php
          $heading = array();
          $contents = array();

          switch ($action) {
            case 'edit':
              $heading[] = array('text' => '<h4>' . $cInfo->configuration_title . '</h4>');

              if ($cInfo->set_function) {
                eval('$value_field = ' . $cInfo->set_function . '"' . htmlspecialchars($cInfo->configuration_value, ENT_COMPAT, CHARSET, TRUE) . '");');
              } else {
                $value_field = zen_draw_input_field('configuration_value', htmlspecialchars($cInfo->configuration_value, ENT_COMPAT, CHARSET, TRUE), 'size="60" class="cfgInput form-control col-md-3" id="full-popover" data-color-format="hex"');
              }

              $contents = array('form' => zen_draw_form('configuration', FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . (int)$cInfo->configuration_id . '&action=save'));
              if (ADMIN_CONFIGURATION_KEY_ON == 1) {
                $contents[] = array('text' => '<strong>Key: ' . $cInfo->configuration_key . '</strong><br>');
              }
              $contents[] = array('text' => TEXT_INFO_EDIT_INTRO);
              $contents[] = array('text' => '<strong>' . $cInfo->configuration_title . '</strong><br>' . $cInfo->configuration_description . '<br>' . $value_field);
              $contents[] = array('align' => 'center', 'text' => '<button type="submit" class="btn btn-primary" name="submit' . $cInfo->configuration_key . '">' . IMAGE_UPDATE . '</button>&nbsp;<a href="' . zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . $cInfo->configuration_id) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
              break;
            default:
              if (isset($cInfo) && is_object($cInfo)) {
                $heading[] = array('text' => '<h4>' . $cInfo->configuration_title . '</h4>');
                if (ADMIN_CONFIGURATION_KEY_ON == 1) {
                  $contents[] = array('text' => '<strong>Key: ' . $cInfo->configuration_key . '</strong><br>');
                }

                $contents[] = array('align' => 'center', 'text' => '<a href="' . zen_href_link(FILENAME_ZCA_BOOTSTRAP_COLORS, 'cID=' . $cInfo->configuration_id . '&action=edit') . '" class="btn btn-primary">' . IMAGE_EDIT . '</a>');
                $contents[] = array('text' => $cInfo->configuration_description);
                $contents[] = array('text' => TEXT_INFO_DATE_ADDED . ' ' . zen_date_short($cInfo->date_added));
                if (zen_not_null($cInfo->last_modified)) {
                  $contents[] = array('text' => TEXT_INFO_LAST_MODIFIED . ' ' . zen_date_short($cInfo->last_modified));
                }
              }
              break;
          }

          if (!empty($heading) && !empty($contents)) {
            $box = new box;
            echo $box->infoBox($heading, $contents);
          }
          ?>
        </div>
      </div>
    </div>
    <link rel="stylesheet" href="includes/css/colorpicker.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinycolor/0.11.1/tinycolor.min.js"></script>
    <script src="includes/javascript/colorpicker.js"></script>

    <script>
      $(document).ready(function () {
        $("input#full-popover").ColorPickerSliders({
          placement: 'bottom',
          hsvpanel: true,
          previewformat: 'hex'
        });
      });
    </script>
    <!-- body_eof //-->

    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->
    <br>
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
