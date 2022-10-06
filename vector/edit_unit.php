<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
//-Last modified 20220302-lat9 Edit Orders v4.6.1
//
require 'includes/application_top.php';
<!-- footer //-->
<?php 
require DIR_WS_INCLUDES . 'footer.php'; 
?>
<!-- footer_eof //-->
</body>
</html>
<?php
unset($_SESSION['customer_id'], $_SESSION['customer_country_id'], $_SESSION['customer_zone_id'], $_SESSION['cart'], $_SESSION['shipping'], $_SESSION['payment']);
require DIR_WS_INCLUDES . 'application_bottom.php';
?>