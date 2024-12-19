<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class for Open WP Cross-Selling
 * 
 * Responsible for:
 * - Loading textdomain
 * - Initializing front and admin logic
 * - Setting up necessary hooks
 */
class OWCS_Plugin {

    /**
     * Initialize the plugin's functionality.
     */
    public function init() {
        $this->load_textdomain();

        require_once OWCS_DIR . '/includes/class-owcs-product-data.php';
        require_once OWCS_DIR . '/includes/class-owcs-front.php';
        $front = new OWCS_Front();
        $front->init();

        $product_data = new OWCS_Product_Data();
        $product_data->init();

        if ( is_admin() ) {
            require_once OWCS_DIR . '/includes/class-owcs-admin.php';
            require_once OWCS_DIR . '/includes/class-owcs-settings.php';

            $admin = new OWCS_Admin();
            $admin->init();

            $settings = new OWCS_Settings();
            $settings->init();
        }
    }

    /**
     * Load plugin textdomain for translations.
     */
    protected function load_textdomain() {
        load_plugin_textdomain(
            'open-wp-cross-selling',
            false,
            dirname(plugin_basename(OWCS_FILE)) . '/languages'
        );
    }

}
