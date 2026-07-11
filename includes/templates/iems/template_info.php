<?php
/**
 * Template Information File
 *
 * IEMS v1.0.0
 *
 * IEMS is a "child" template: it inherits everything from the ZCA Bootstrap-4
 * template (below) and only ships the files it needs to override or add.
 * Any file not present here is transparently loaded from the 'bootstrap'
 * template directory (and, failing that, from 'template_default'), via the
 * $template_parent setting below.
 *
 * @copyright Copyright 2003-2005 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * Modified for Indianapolis EMS, 2026, Jeremiah Cook
 */
$template_name = 'IEMS';
$template_version = 'Version 1.0.0';
$template_author = 'Jeremiah Cook, based on the ZCA Bootstrap-4 template by rbarbour, lat9, drbyte';
$template_description = 'IEMS storefront template for Indianapolis EMS. Built as a customization layer on top of the ZCA Bootstrap-4 template (Bootstrap 4.6.2 / Font Awesome 6.5.2): unmodified Bootstrap files are inherited automatically, while IEMS-specific markup, styling, and behavior live in this template folder. See docs/iems/readme.html for the full list of inherited vs. overridden files.';
$template_screenshot = 'ZCA_BOOTSTRAP_TEMPLATE.png';

// -----
// Name of the template this one inherits from. Any template file (common/,
// templates/, sideboxes/, centerboxes/, modalboxes/, css/, jscript/, images/,
// buttons/, etc.) not found in this template's own directory is looked up in
// the parent template's directory before falling back to template_default.
// This is consumed by includes/init_includes/init_templates.php, which
// defines the DIR_WS_TEMPLATE_PARENT constant from this value.
//
$template_parent = 'bootstrap';

// -----
// This setting's effect will require a change to be provided in zc158a, essentially enabling
// the admin's "Layout Controller" to also display the single-column layout settings.
//
$uses_single_column_layout_settings = true;

// -----
// Instructs the "Layout Controller" that this template doesn't use
// the 'Mobile-Menu' boxes.
//
$uses_mobile_sidebox_settings = false;
