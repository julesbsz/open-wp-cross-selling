<?php
/**
 * Plugin Name: Open WP Cross-Selling
 * Plugin URI: https://github.com/julesbsz/open-wp-cross-selling
 * Description: Wordpress plugin that displays a popup with crossed products when adding to cart.
 * Version: 1.3.0
 * Author: Jules Bousrez
 * Author URI: https://julesbousrez.fr/
 * Text Domain: open-wp-cross-selling
 * Requires PHP: 7.4
 *
 * Copyright 2024 Jules Bousrez
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define('OWCS_VERSION', '1.3.0');
define('OWCS_RELEASE_TIMESTAMP', '2024-12-05 17:30');
define('OWCS_NAME', 'Open WP Cross-Selling');
define('OWCS_TEXT_DOMAIN', 'open-wp-cross-selling');
define('OWCS_SLUG', 'open-wp-cross-selling');
define('OWCS_FILE', __FILE__);
define('OWCS_DIR', __DIR__);

// Include main plugin class
require_once OWCS_DIR . '/includes/class-owcs-plugin.php';

/**
 * Initialize the plugin.
 */
function owcs_init_plugin() {
    $owcs_plugin = new OWCS_Plugin();
    $owcs_plugin->init();
}
add_action('plugins_loaded', 'owcs_init_plugin');
