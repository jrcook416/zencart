<?php
/**
 * IEMS site-specific admin overrides (Zen Cart's own override file; see dist-site_specific_admin_overrides.php).
 *
 * Admin passwords never expire. This carries forward the 1.5.6 stores' setting
 * (minion/login.php, "Administrative password reset policy removal", 2019-10-03),
 * so admins keep signing in with their existing passwords after the 2.2 upgrade.
 */
define('ADMIN_PASSWORD_EXPIRES_INTERVAL', strtotime('- 36500 day'));
