<?php
// -----
// Part of the Edit Orders plugin for Zen Cart, provided by lat9 and others.
//
// Copyright (c) 2003 The zen-cart developers
//
//-Last modified 20220302-lat9 Edit Orders v4.6.1
//
require 'includes/application_top.php';
?>
<html <?php echo HTML_PARAMS; ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
<?php
require DIR_WS_INCLUDES . 'header.php';?>
<center><h1>Indianapolis EMS Unit Editor</h1></center>
<div class="container">
    <div class="col-sm"></div>
    <div class="col-sm">
<?php
        county_lookup();
        echo iems_pull_down_menu('entry_county', $county_array, $cInfo->entry_county, 'id ="entry_county" class = "form-control width:25px" data-live-search="true"');?>
		<br><button type="button" class="btn btn-primary btn-lrg addAgency">Load This County's Agencies into the Agency Selector</button>
    </div>
    <div class="col-sm"></div>
</div>

<!-- footer //-->
<?php 
require DIR_WS_INCLUDES . 'footer.php'; 
?>
<!-- footer_eof //-->
</body>
</html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
?>