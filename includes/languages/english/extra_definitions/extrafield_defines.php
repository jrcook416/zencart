<?php
//
// +----------------------------------------------------------------------+
// |zen-cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 The zen-cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the zen-cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
// $Id: spanish.php 277 13-02-2022 modificdo por MAUARI $
//

define('DISPLAY_PAGE_PARSE_TIME', 'Tiempo usado');
define('ENTRY_PASSOWRD_CONFIRMATION_TEXT', 'Confirme la contraseña');
define('ENTRY_STATE_TEXT_HOLDER', '*');
define('EXTRAFIELD_TITLE', 'EXTRAFIELD TITLE');
define('EXTRAFIELD_TITLE2', 'EXTRAFIELD TITLE 2');
define('ENTRY_EXTRAFIELD', 'EXTRAFIELD');
define('ENTRY_EXTRAFIELD_ERROR', 'EXTRAFIELD ERROR');


define('ENTRY_EXTRAFIELD2', 'EXTRAFIELD 2');
define('ENTRY_EXTRAFIELD2_ERROR', 'EXTRAFIELD 2 ERROR');

define('ENTRY_EXTRAFIELD3', 'EXTRAFIELD 3');
define('ENTRY_EXTRAFIELD3_ERROR', 'EXTRAFIELD 3 ERROR');


define('ENTRY_EXTRAFIELD4', 'EXTRAFIELD 4');
define('ENTRY_EXTRAFIELD4_ERROR', 'EXTRAFIELD 4 ERROR');
if (EXTRAFIELD_REQUIRED == 'true') {
  define('ENTRY_EXTRAFIELD_TEXT', 'EXTRAFIELD required');
} else {
  define('ENTRY_EXTRAFIELD_TEXT', 'EXTRAFIELD information ');
}

if (EXTRAFIELD_REQUIRED2 == 'true') {
  define('ENTRY_EXTRAFIELD2_TEXT', 'EXTRAFIELD 2 required');
  define('ENTRY_EXTRAFIELD2_TEXT_MIN_LENGTH', 'MIN LENGTH');
} else {

define('ENTRY_EXTRAFIELD2_TEXT', 'EXTRAFIELD 2 information');
define('ENTRY_EXTRAFIELD2_TEXT_MIN_LENGTH', 'MIN LENGTH');  
}

if (EXTRAFIELD_REQUIRED3 == 'true') {
  define('ENTRY_EXTRAFIELD3_TEXT', 'EXTRAFIELD 3 required');
  define('ENTRY_EXTRAFIELD3_TEXT_MIN_LENGTH', 'minimo requerido');
} else {

  define('ENTRY_EXTRAFIELD3_TEXT', 'EXTRAFIELD 3 information');
  define('ENTRY_EXTRAFIELD3_TEXT_MIN_LENGTH', 'MIN LENGTH');
  }
if (EXTRAFIELD_REQUIRED4 == 'true') {
  define('ENTRY_EXTRAFIELD4_TEXT', 'EXTRAFIELD 4 required');
  define('ENTRY_EXTRAFIELD4_TEXT_MIN_LENGTH', 'MIN LENGTH');
} else {
  
  define('ENTRY_EXTRAFIELD4_TEXT', 'EXTRAFIELD 4 information');
  define('ENTRY_EXTRAFIELD4_TEXT_MIN_LENGTH', 'minimo requerido');
}


?>