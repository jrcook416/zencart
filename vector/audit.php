<?php
/**
 * @package admin
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  Sat Oct 17 21:23:07 2015 -0400 Modified in v1.5.5 $
 */

  require('includes/application_top.php');
  $safe_cat = find_safe_category();

$action = (isset($_GET['action']) ? $_GET['action'] : '');
if (zen_not_null($action)) {
  switch ($action) {
    case 'del_cat':
      $cat = zen_db_input($_GET['cat']); 
      $check = $db->Execute("DELETE FROM " . TABLE_CATEGORIES .  " WHERE categories_id = " . $cat);
      zen_redirect(zen_href_link(FILENAME_AUDIT, '')); 

    case 'add_mcat_pair':
      $prid = zen_db_input($_GET['prid']); 
      $mcat = zen_db_input($_GET['mcat']); 
      $check = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS_TO_CATEGORIES .  " WHERE products_id = " . $prid . " AND categories_id = " . $mcat); 
      if ($check->EOF) { 
        $sql_data_array = array('products_id' => $prid, 'categories_id'=>$mcat); 
        zen_db_perform(TABLE_PRODUCTS_TO_CATEGORIES, $sql_data_array); 
      }
      zen_redirect(zen_href_link(FILENAME_AUDIT, '')); 

    case 'set_mcat':
      $prid = zen_db_input($_GET['prid']); 
      $ptype = zen_db_input($_GET['ptype']); 
      $mcat = $safe_cat; 
      $db->Execute("UPDATE " . TABLE_PRODUCTS . " SET master_categories_id = " . $mcat . " WHERE products_id = " . $prid . " LIMIT 1"); 
      $db->Execute("DELETE FROM " . TABLE_PRODUCTS_TO_CATEGORIES .  " WHERE products_id = " . $prid);
      $sql_data_array = array('products_id' => $prid, 'categories_id'=>$mcat); 
      zen_db_perform(TABLE_PRODUCTS_TO_CATEGORIES, $sql_data_array); 
      zen_redirect(zen_href_link(FILENAME_CATEGORY_PRODUCT_LISTING, 'cPath=' . $mcat. '&product_type=' . $ptype . '&pID=' . $prid . '&action=move_product')); 
  }
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" media="print" href="includes/stylesheet_print.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
</script>
<style>
    table, th, td {
        border: 1px solid red;
        text-align: left;
    }
    th, td {
        padding: 10px;
        background-color:none;
    }
</style>
</head>
<body onload="init()">
<?php if ($action == '') { ?>
<div id="spinner"><img src="images/audit_spinner.gif" /></div>
<?php } ?>
<!-- header //-->
<div class="header-area">
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
</div>
<!-- header_eof //-->

<!-- body //-->
<table class="issues" width="80%" cellspacing="10" cellpadding="10" border="1">
<!-- body_text //-->

   <tr class="issues">
     <td class="issues">
         <b><?php echo PRODUCT_HEADING; ?></b>
     </td>
     <td class="issues">
         <b><?php echo ISSUE_HEADING; ?></b>
     </td>
   </tr>

<?php
sleep(2); 
$issues_found = 0; 

$all_products = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS); 
while (!$all_products->EOF) { 
  $prid = $all_products->fields['products_id']; 
  $mcat = $all_products->fields['master_categories_id'];  
  $expected = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS_TO_CATEGORIES  . " WHERE products_id= " . $prid . " AND categories_id = " . $mcat); 
  $present = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS_TO_CATEGORIES  . " WHERE products_id= " . $prid); 
  $mcat_exists_query = $db->Execute("SELECT * FROM " . TABLE_CATEGORIES  . " WHERE categories_id = " . $mcat); 
  $mcat_exists = !$mcat_exists_query->EOF; 
  $issue = false; 
  $options = array(); 
  $fields = array(); 
  if (!$mcat_exists) { 
      $issue = true; 
      $prod_info = zen_get_products_name ($prid); 
      $issue_info = MCAT_NOT_PRESENT;
      $options[] = FIX_OPTION_SET_MCAT; 
      $fields = array(KEY_PRID, KEY_MCAT); 
  } else if ($mcat == 0) {
      $issue = true; 
      $prod_info = zen_get_products_name ($prid); 
      $issue_info = PRODUCT_MCAT_0;
      $options[] = FIX_OPTION_SET_MCAT; 
      $fields = array(KEY_PRID, KEY_MCAT); 
  } else if ($mcat == $safe_cat) {
      $issue = true; 
      $prod_info = zen_get_products_name ($prid); 
      $issue_info = PRODUCT_MCAT_SAFE;
      $options[] = FIX_OPTION_SET_MCAT; 
      $fields = array(KEY_PRID, KEY_MCAT); 
  } else if ($present->EOF) {
      $issue = true; 
      $prod_info = zen_get_products_name ($prid); 
      $issue_info = PRODUCT_NOT_PRESENT_P2C;
      $options[] = FIX_OPTION_ADD_PAIR; 
      $fields = array(KEY_PRID, KEY_MCAT); 
  } else if ($expected->EOF) {
      $issue = true; 
      $prod_info = zen_get_products_name ($prid); 
      $issue_info = PRODUCT_MCAT_PAIR_NOT_PRESENT_P2C; 
      $options[] = FIX_OPTION_ADD_PAIR; 
      $fields = array(KEY_PRID, KEY_MCAT); 
  }
  if ($issue) { 
    $issues_found++; 
?>
   <tr class="issues">
     <td class="issues">
        <?php 
          echo $prod_info ."<br />";
          if (in_array(KEY_PRID, $fields)) {
            echo FIELD_PRODUCTS_ID . ": " . $all_products->fields['products_id'] . "<br />"; 
          }
          if (in_array(KEY_MCAT, $fields)) {
            echo FIELD_MASTER_CATEGORIES_ID . ": " . $all_products->fields['master_categories_id'] . "<br />"; 
          }
        ?>
     </td>
     <td class="issues">
         <?php 
          echo $issue_info . "<br />";
          if (in_array(FIX_OPTION_ADD_PAIR, $options)) { 
            // No target blank for this one
            echo '<a href="' . zen_href_link(FILENAME_AUDIT,'action=add_mcat_pair&prid='.$prid.'&mcat='.$mcat) . '">' . TEXT_ADD_MCAT_PAIR . '</a>'; 
          } 
          if (in_array(FIX_OPTION_SET_MCAT, $options)) { 
            echo '<a href="' . zen_href_link(FILENAME_AUDIT,'action=set_mcat&prid='.$prid .'&ptype='.$all_products->fields['products_type']) . '" target="_blank">' . TEXT_MOVE_PROD . '</a>'; 
            echo '<br />'; 
            echo TEXT_SET_MCAT_WARNING; 
          } 
          ?>
         <br />
     </td>
   </tr>
<?php
  }
  $all_products->MoveNext();
}
?>

<?php
$all_products = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS . " WHERE products_type = 0"); 
while (!$all_products->EOF) { 
  $prid = $all_products->fields['products_id']; 
  $prod_info = zen_get_products_name ($prid); 
  $issue_info = PRODUCT_TYPE_0;
  $options = array(FIX_OPTION_SET_TYPE); 
  $fields = array(KEY_PRID); 
  $issues_found++; 
?>
   <tr class="issues">
     <td class="issues">
        <?php 
          echo $prod_info ."<br />";
          if (in_array(KEY_PRID, $fields)) {
            echo FIELD_PRODUCTS_ID . ": " . $all_products->fields['products_id'] . "<br />"; 
          }
        ?>
     </td>
     <td class="issues">
         <?php 
          echo $issue_info . "<br />";
          if (in_array(FIX_OPTION_SET_TYPE, $options)) { 
            echo '<a href="' . zen_href_link(FILENAME_AUDIT,'action=set_type&prid='.$prid) . '" target="_blank">' . TEXT_SET_TYPE. '</a>'; 
          } 
          ?>
         <br />
     </td>
   </tr>
<?php
  $all_products->MoveNext();
}

$all_categories = $db->Execute("SELECT * FROM " . TABLE_CATEGORIES); 
while (!$all_categories->EOF) { 
  $cat = $all_categories->fields['categories_id'];  
  // Does the category contain products? 
  $has_products = false; 
  $q = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " WHERE categories_id = " . $all_categories->fields['categories_id'] . " LIMIT 1"); 
  if (!$q->EOF) {
     $prid = $q->fields['products_id']; 
     $has_products = true; 
     $prod_count = $q->RecordCount(); 
  }

  $has_categories = false; 
  $q = $db->Execute("SELECT * FROM " . TABLE_CATEGORIES . " WHERE parent_id = " . $all_categories->fields['categories_id'] . " LIMIT 1"); 
  if (!$q->EOF) {
     $has_categories = true; 
  }
  
  if (!$has_products && !$has_categories) { 
     // Category appears empty
     $cat_info = zen_get_category_name ($cat, $_SESSION['languages_id']); 
     if ($cat_info == "SAFE") {
       $all_categories->MoveNext();
       continue;
     }
   
     $issue_info = CAT_EMPTY;
     $options = array(FIX_OPTION_DEL_CAT); 
     $fields = array(KEY_CAT); 
     $issues_found++; 
   ?>
      <tr class="issues">
        <td class="issues">
           <?php 
             echo $cat_info ."<br />";
             if (in_array(KEY_CAT, $fields)) {
               echo FIELD_CATEGORIES_ID . ": " . $cat . "<br />"; 
             }
           ?>
        </td>
        <td class="issues">
            <?php 
             echo $issue_info . "<br />";
             if (in_array(FIX_OPTION_DEL_CAT, $options)) { 
               // No target blank for this one
               echo '<a href="' . zen_href_link(FILENAME_AUDIT,'action=del_cat&cat='.$cat) . '">' . TEXT_DELETE_CAT . '</a>'; 
             }
             ?>
            <br />
        </td>
      </tr>
   <?php
  } // category empty 

  else if ($has_products && $has_categories) { 
     $issue_info = CAT_PRODS_CATS;
     $options = array(FIX_OPTION_SET_MCAT); 
     $fields = array(KEY_CAT, KEY_PRID); 
     $issues_found++; 
   ?>
      <tr class="issues">
        <td class="issues">
           <?php 
             echo $cat_info ."<br />";
             if (in_array(KEY_CAT, $fields)) {
               echo FIELD_CATEGORIES_ID . ": " . $cat . "<br />"; 
             }
             if (in_array(KEY_PRID, $fields)) {
               echo FIELD_PRODUCTS_ID . ": " . $prid . "<br />"; 
             }
           ?>
        </td>
        <td class="issues">
            <?php 
             echo $issue_info . "<br />";
             echo sprintf(WRONG_PRODS_COUNT,$prod_count) . '<br>';
             echo USE_ADMIN_PHPMYADMIN . '<br>';  
             ?>
            <br />
        </td>
      </tr>
<?php
  } // category has prods and cats 

  $all_categories->MoveNext();
}
?>

<?php if ($issues_found == 0) { ?>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
     // Handler when all assets are loaded
       $(".issues").css({"border-color":"green"});
    });
  </script>
  <tr class="issues"><td class="issues" colspan="3"><?php echo NO_ISSUES_FOUND; ?></td></tr>
<?php } ?>
<!-- body_text_eof //-->
</table>
<!-- body_eof //-->
<!-- footer //-->
<div class="footer-area">
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
</div>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function(){
  if (document.getElementById("spinner")) { 
    document.getElementById("spinner").style.visibility = "hidden";
    document.getElementById("spinner").style.height = 0;
  }
});
</script>
<?php
function find_safe_category() {
  global $db; 

  $query = "SELECT categories_id FROM " . TABLE_CATEGORIES_DESCRIPTION  . " WHERE categories_name = 'SAFE'"; 
  $rows = $db->Execute($query);
  if ($rows->EOF) { 
    die("Please create a category named SAFE at the top of your category hierarchy");
  }
  $mcat = $rows->fields['categories_id'];
  return $mcat; 
}
