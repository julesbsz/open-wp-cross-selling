<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Front-end logic for the OWCS plugin.
 * 
 * Handles:
 * - Rendering the aside template on product pages
 * - Enqueuing front-end assets
 * - Setting cookies when products are added to cart
 * - AJAX actions related to front-end if any
 */
class OWCS_Front {

    /**
     * Initialize front-end hooks.
     */
    public function init() {
        add_action('wp_footer', array($this, 'render_aside'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('woocommerce_add_to_cart', array($this, 'set_added_to_cart_cookie'), 10, 2);
        add_filter('woocommerce_add_to_cart_redirect', array($this, 'custom_redirect_function'), 99);

        add_action('wp_ajax_owcs_get_preset_products', array($this, 'ajax_get_preset_products'));
        add_action('wp_ajax_nopriv_owcs_get_preset_products', array($this, 'ajax_get_preset_products'));
    }

    /**
     * Render the aside template on single product pages.
     */
    public function render_aside() {
        if (!is_product()) {
            return;
        }

        require_once OWCS_DIR . '/views/front/aside.php';
    }

    /**
     * Enqueue frontend CSS and JS for product pages.
     */
    public function enqueue_assets() {
        if (!is_product()) {
            return;
        }

        wp_enqueue_style(
            'owcs-style',
            plugins_url('assets/css/style.css', OWCS_FILE),
            array(),
            OWCS_VERSION
        );

        wp_enqueue_script(
            'owcs-script',
            plugins_url('assets/js/script.js', OWCS_FILE),
            array('jquery', 'wc-add-to-cart'),
            OWCS_VERSION,
            false
        );

        wp_localize_script('owcs-script', 'owcsTranslations', array(
            'viewCart' => __('View cart', 'open-wp-cross-selling'),
        ));
    }

    /**
     * Set a cookie when a product is added to cart if the modal is enabled for that product.
     *
     * @param string $cart_item_key The cart item key.
     * @param int $product_id The product ID added to the cart.
     */
    public function set_added_to_cart_cookie($cart_item_key, $product_id) {
        $enable_modal = get_post_meta($product_id, '_owcs_enable_modal', true);

        if ($enable_modal === 'yes') {
            setcookie('owcs_added_to_cart', '1', time() + 60, COOKIEPATH, COOKIE_DOMAIN);

            $product = wc_get_product($product_id);
            if ($product) {
                setcookie('owcs_added_product_name', urlencode($product->get_name()), time() + 60, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }

    /**
     * Custom redirect after add to cart.
     *
     * @param string $url Original redirect URL.
     * @return string Modified redirect URL.
     */
    public function custom_redirect_function($url) {
        $product_id = ( isset($_REQUEST['add-to-cart']) ) ? intval($_REQUEST['add-to-cart']) : 0;

        $enable_modal = get_post_meta($product_id, '_owcs_enable_modal', true);

        if ($enable_modal === 'yes') {
            return get_permalink($product_id);
        } else {
            return wc_get_checkout_url();
        }
    }

    /**
     * AJAX callback to get products from a preset.
     */
    public function ajax_get_preset_products() {
        check_ajax_referer('owcs_preset_nonce', 'nonce');

        $preset_id = $_POST['preset_id'];
        $presets   = get_option('owcs_presets', array());

        if (isset($presets[$preset_id])) {
            wp_send_json_success($presets[$preset_id]['products']);
        }

        wp_send_json_error();
    }
}
