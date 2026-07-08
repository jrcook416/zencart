<?php
/**
 * zen_add_filemtime.php: Provides a zc300 function for use by earlier ZC versions,
 *
 */
if (!function_exists('zen_add_filemtime')) {
    /**
     * Add a 'cache-busting' parameter to a CSS/JS file, using its last-modified timestamp.
     *
     * Storefront usage takes only the first parameter, since all storefront CSS/JS files (whether
     * in the /includes/templates subdirectories or in a zc_plugin) are within
     * DIR_FS_CATALOG.
     *
     * Admin usage takes either one (for CSS/JS files located in the 'base' admin/includes directory) or
     * two parameters when a file is located in a zc_plugin's admin/includes directory.
     *
     * @since ZC v3.0.0
     */
    function zen_add_filemtime(string $relative_path, ?string $absolute_path = null): string
    {
        if (IS_ADMIN_FLAG === true) {
            $absolute_path ??= DIR_FS_ADMIN . $relative_path;
        } else {
            $absolute_path = DIR_FS_CATALOG . $relative_path;
        }

        if (!is_file($absolute_path)) {
            return $relative_path;
        }

        $mtime = filemtime($absolute_path);
        if ($mtime === false) {
            return $relative_path;
        }
        return $relative_path . '?' . $mtime;
    }
}
