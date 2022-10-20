<?php
require("includes/application_top.php"); 
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
<link rel="stylesheet" type="text/css" href="includes/admin_access.css" />
<style>
.db_table, .db_tr, .db_td {
  border: 1px solid black;
}
#table_list {
  width: 90%; 
  word-wrap: break-word;
  line-height: 150%;
}
</style>
<script language="javascript" src="includes/menu.js"></script>
<script language="javascript" src="includes/general.js"></script>
<script type="text/javascript">
  <!--
  function init()
  {
    cssjsmenu('navbar');
    if (document.getElementById)
    {
      var kill = document.getElementById('hoverJS');
      kill.disabled = true;
    }
  }
  // -->
</script>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<div id="pageWrapper">
  <h1><?php echo HEADING_TITLE ?></h1>
<br />
<?php echo LEGEND_TITLE . LEGEND_1 . ',&nbsp;' . LEGEND_2 . ".<br />"; ?>
<br /><br />
<?php 
$tables = $db->Execute("SHOW TABLES"); 

echo '<div id="table_list">'; 
while (!$tables->EOF) { 
  $table = $tables->fields[array_keys($tables->fields)[0]]; 
  echo '<a href="#' . $table . '">' . $table . '</a>' . ' &nbsp;&nbsp; '; 
  $tables->MoveNext(); 
}
echo '</div>';

if (method_exists($tables,'rewind')) { 
  $tables->rewind(); 
} else {
  $tables->Move(0); 
}
while (!$tables->EOF) { 
  foreach ($tables->fields as $key => $value) {
    $table = $value; 
    break; 
  }
  echo '<h2 id="' . $table . '">' . $table . '</h2>'; 
  $query = $db->Execute("SHOW FULL COLUMNS FROM $table");
  echo '<table class="db_table">'; 
  echo TABLE_HEADING; 
  $line = 1; 
  while (!$query->EOF) { 
    echo '<tr class="db_tr">'; 
    foreach ($query->fields as $key => $value) {
      if ($key == "Privileges") continue; 
      if ($key == "Field") { 
         if ($line == 1) { 
            echo '<td class="db_td">' . print_value_nonlink($value, $query->fields['Key']) . '</td>'; 
         } else if (!foreign_key($value) && strpos($value, "_id") === FALSE) { 
            echo '<td class="db_td">' . print_value_nonlink($value, $query->fields['Key']) . '</td>'; 
         } else {
            echo '<td class="db_td">' . print_value_link($value) . '</td>'; 
         }
      } else {
            echo '<td class="db_td">' . $value . '</td>'; 
      }
    }
    echo '</tr>'; 
    $line++; 
    $query->MoveNext(); 
  }

  echo '</table>'; 
  $tables->MoveNext(); 
}


?>
</div>
<!-- body_eof //-->

<div class="bottom">
<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</div>
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>

<?php
function print_value_link($value) {
  switch ($value) { 
  case "customers_id": 
  case "customer_id": 
    return '<a href="#customers">' . $value . '</a>'; 

  case "products_id": 
  case "product_id": 
  case "products_prid": 
    return '<a href="#products">' . $value . '</a>'; 

  case "orders_id": 
  case "order_id": 
    return '<a href="#orders">' . $value . '</a>'; 

  case "banners_id": 
    return '<a href="#banners">' . $value . '</a>'; 

  case "language_id": 
  case "languages_id": 
    return '<a href="#languages">' . $value . '</a>'; 

  case "coupon_id": 
    return '<a href="#coupons">' . $value . '</a>'; 

  case "categories_id": 
  case "master_categories_id": 
  case "category_id": 
    return '<a href="#categories">' . $value . '</a>'; 

  case "orders_products_id": 
    return '<a href="#orders_products">' . $value . '</a>'; 

  case "admin_id": 
    return '<a href="#admin">admin_id</a>'; 

  case "artists_id": 
    return '<a href="#record_artists">' . $value . '</a>'; 

  case "orders_status_id": 
    return '<a href="#orders_status">' . $value . '</a>'; 

  case "tax_class_id": 
    return '<a href="#tax_class">' . $value . '</a>'; 

  case "zone_id": 
    return '<a href="#zones">' . $value . '</a>'; 

  case "geo_zone_id": 
    return '<a href="#geo_zones">' . $value . '</a>'; 

  case "zone_country_id": 
    return '<a href="#countries">' . $value . '</a>'; 

  case "products_options_id": 
  case "options_id": 
    return '<a href="#products_options">' . $value . '</a>'; 

  case "products_options_value_id": 
  case "options_values_id": 
    return '<a href="#products_options_values">' . $value . '</a>'; 

  case "customers_group_pricing": 
    return '<a href="#group_pricing">' . $value . '</a>'; 

  case "customers_default_address_id": 
    return '<a href="#address_book">' . $value . '</a>'; 
  }
  return $value; 
}

function print_value_nonlink($value, $key) {
  if ($key == "PRI") {
     return "<b>" . $value . "</b>"; 
  } else {
     return $value; 
  }
}

function foreign_key($value) {
  switch ($value) { 
    case "customers_group_pricing": 
      return true; 
    case "products_prid": 
      return true; 
  }
  return false; 
}

