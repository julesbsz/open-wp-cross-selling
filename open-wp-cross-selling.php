<?php
/**
	Plugin Name: Open WP Cross-Selling
	Plugin URI: https://github.com/julesbsz/open-wp-cross-selling
	Description: Wordpress plugin that displays a popup with crossed products when adding to cart.
	Version: 1.0.0
	Author: Jules Bousrez
	Author URI: https://julesbousrez.fr/
	Text Domain: open-wp-cross-selling
	Requires PHP: 7.4

	Copyright 2024 Jules Bousrez

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

define('OWCS_VERSION', '1.3.0');
define('OWCS_RELEASE_TIMESTAMP', '2024-12-05 17:30');
define('OWCS_NAME', 'Open WP Cross-Selling');
define('OWCS_TEXT_DOMAIN', 'open-wp-cross-selling');
define('OWCS_SLUG', 'open-wp-cross-selling');
define('OWCS_FILE', __FILE__);
define('OWCS_DIR', __DIR__);

// Load languages
function owcs_load_textdomain() {
    load_plugin_textdomain(
        'open-wp-cross-selling',
        false,
        dirname(plugin_basename(OWCS_FILE)) . '/languages'
    );
}
add_action('init', 'owcs_load_textdomain');

require_once OWCS_DIR . '/admin/product-data.php';
require_once OWCS_DIR . '/admin/woocommerce-settings.php';

// Load drawer template
add_action('wp_footer', 'owcs_render_aside');
function owcs_render_aside() {
    if (!is_product()) return;

    require_once OWCS_DIR . '/views/front/aside.php';
}


// Load styles and scripts
add_action('wp_enqueue_scripts', 'owcs_enqueue_assets');
function owcs_enqueue_assets() {
    if (!is_product()) return;
    
    wp_enqueue_style(
        'owcs-style',
        plugins_url('assets/css/style.css', __FILE__),
        array(),
        OWCS_VERSION
    );

    wp_enqueue_script(
        'owcs-script',
        plugins_url('assets/js/script.js', __FILE__),
        array('jquery', 'wc-add-to-cart'),
        OWCS_VERSION,
        false
    );

    wp_localize_script('owcs-script', 'owcsTranslations', array(
        'viewCart' => __('View cart', 'open-wp-cross-selling'),
    ));
}

// Add cookie when product is added to cart
add_action('woocommerce_add_to_cart', 'owcs_set_added_to_cart_cookie', 10, 2);
function owcs_set_added_to_cart_cookie($cart_item_key, $product_id) {
    $enable_modal = get_post_meta($product_id, '_owcs_enable_modal', true);
    
    if ($enable_modal === 'yes') {
        setcookie('owcs_added_to_cart', '1', time() + 60, COOKIEPATH, COOKIE_DOMAIN);
        
        $product = wc_get_product($product_id);
        if ($product) {
            setcookie('owcs_added_product_name', urlencode($product->get_name()), time() + 60, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}

add_filter( 'woocommerce_add_to_cart_redirect', 'custom_redirect_function', 99 );
function custom_redirect_function($url) {
    $product_id = ( isset($_REQUEST['add-to-cart']) ) ? intval($_REQUEST['add-to-cart']) : 0;
    
    $enable_modal = get_post_meta($product_id, '_owcs_enable_modal', true);
    
    if ($enable_modal === 'yes') {
        return get_permalink( $product_id );
    } else {
        return wc_get_checkout_url();
    }
}

// Ajouter la fonction AJAX pour récupérer les produits du preset
add_action('wp_ajax_owcs_get_preset_products', 'owcs_get_preset_products');
function owcs_get_preset_products() {
    check_ajax_referer('owcs_preset_nonce', 'nonce');

    $preset_id = $_POST['preset_id'];
    $presets = get_option('owcs_presets', array());

    if (isset($presets[$preset_id])) {
        wp_send_json_success($presets[$preset_id]['products']);
    }

    wp_send_json_error();
}

// Sauvegarder le preset sélectionné et copier ses produits
add_action('woocommerce_process_product_meta', 'owcs_save_product_preset');
function owcs_save_product_preset($post_id) {
    // Sauvegarder le preset sélectionné
    if (isset($_POST['_owcs_preset'])) {
        $preset_id = sanitize_text_field($_POST['_owcs_preset']);
        update_post_meta($post_id, '_owcs_preset', $preset_id);

        // Si un preset est sélectionné, copier ses produits
        if (!empty($preset_id)) {
            $presets = get_option('owcs_presets', array());
            if (isset($presets[$preset_id])) {
                update_post_meta($post_id, '_owcs_modal_products', $presets[$preset_id]['products']);
            }
        }
    }

    // Si des produits ont été sélectionnés manuellement, ils écrasent ceux du preset
    if (isset($_POST['_owcs_modal_products'])) {
        $products = array_map('intval', (array) $_POST['_owcs_modal_products']);
        update_post_meta($post_id, '_owcs_modal_products', $products);
    }
}

// Modifier le JavaScript pour mettre à jour les deux champs
add_action('admin_footer', 'owcs_preset_script');
function owcs_preset_script() {
    if (!isset($_GET['post']) || get_post_type($_GET['post']) !== 'product') return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#_owcs_preset').on('change', function() {
            const presetId = $(this).val();
            if (!presetId) return;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'owcs_get_preset_products',
                    preset_id: presetId,
                    nonce: '<?php echo wp_create_nonce("owcs_preset_nonce"); ?>'
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Mettre à jour le select des produits
                        const $productSelect = $('#_owcs_modal_products');
                        $productSelect.val(response.data).trigger('change');
                    }
                }
            });
        });
    });
    </script>
    <?php
} 