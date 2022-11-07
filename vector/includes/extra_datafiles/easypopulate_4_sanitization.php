<?php
if (!empty($_SESSION['admin_id']) && $_SERVER['SCRIPT_NAME'] === DIR_WS_ADMIN . 'easypopulate_4.php') {
//define('DO_DEBUG_SANITIZATION', true);
//define('DO_STRICT_SANITIZATION', false);
}
if (class_exists('AdminRequestSanitizer')) {
  $sanitizer = AdminRequestSanitizer::getInstance();
  $group = array(
  'uploadfile' => array(
                        'sanitizerType' => 'FILE_DIR_REGEX',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'ep_export_type' => array(
                        'sanitizerType' => 'CONVERT_INT',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'ep_category_filter' => array(
                        'sanitizerType' => 'CONVERT_INT',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'ep_manufacturer_filter' => array(
                        'sanitizerType' => 'CONVERT_INT',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'ep_status_filter' => array(
                        'sanitizerType' => 'CONVERT_INT',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'export' => array(
                        'sanitizerType' => 'SIMPLE_ALPHANUM_PLUS',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'buttoninsert' => array(
                        'sanitizerType' => 'SIMPLE_ALPHANUM_PLUS',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'exportorder' => array(
                        'sanitizerType' => 'SIMPLE_ALPHANUM_PLUS',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'ep_order_export_type' => array(
                        'sanitizerType' => 'CONVERT_INT',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'configuration' => array(
                        'sanitizerType' => 'SIMPLE_ARRAY',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        'parameters' => array(
                        'order_status' => array(
                          'sanitizerType' => 'CONVERT_INT',
                          'method' => 'post',
                          'pages' => array('easypopulate_4'),
                                               ),
                                             ),
                        ),
  'split' => array(
                        'sanitizerType' => 'FILE_DIR_REGEX',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'split_button' => array(
                        'sanitizerType' => 'SIMPLE_ALPHANUM_PLUS',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'import' => array(
                        'sanitizerType' => 'FILE_DIR_REGEX',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'import_button' => array(
                        'sanitizerType' => 'SIMPLE_ALPHANUM_PLUS',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'delete' => array(
                        'sanitizerType' => 'FILE_DIR_REGEX',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  'delete_button' => array(
                        'sanitizerType' => 'SIMPLE_ALPHANUM_PLUS',
                        'method' => 'post',
                        'pages' => array('easypopulate_4'),
                        ),
  
  
  );
  $sanitizer->addComplexSanitization($group);
} else {

  if ($_SERVER['SCRIPT_NAME'] != DIR_WS_ADMIN . (!strstr(FILENAME_LOGIN, '.php') ? FILENAME_LOGIN . '.php' : FILENAME_LOGIN)) {
    $message = 'WARNING: This site is not fully protected from the items identified in the Trustwave Report discussed at <a href="https://www.zen-cart.com/showthread.php?219732-Trustwave-Security-report-Patch-Included-TWSL2016-006" target="_blank">this Zen-Cart site forum thread</a> ';
    $errors = array('params' => 'class="messageStackAlert alert alert-warning noprint" role="alert"', 'text' => '<i class="fa fa-2x fa-hand-stop-o"></i> ' . $message);

    $tableBox_string = '';

    $tableBox_string .= '<table border="0" width="100%" cellspacing="0" cellpadding="2" class="noprint">' . "\n";
    $tableBox_string .= '  <tr ' . $errors['params'] . '>' . "\n";

    $tableBox_string .= '    <td' . ' ' . $errors['params'] . '>' . $errors['text'] . '</td>' . "\n";
    $tableBox_string .= '  </tr>' . "\n";
    $tableBox_string .= '</table>' . "\n";

    echo $tableBox_string;
  }

}