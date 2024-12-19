<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles additional product data fields and saving for cross-selling.
 * 
 * This class replicates and organizes the logic from `product-data.php`.
 */
class OWCS_Product_Data {

    /**
     * Initialize product data related hooks.
     */
    public function init() {
        add_action('woocommerce_product_options_related', array($this, 'owcs_add_product_options'));
        add_action('woocommerce_process_product_meta', array($this, 'owcs_save_product_options'));
        add_action('woocommerce_process_product_meta', array($this, 'owcs_save_product_preset'));

        // The following function was previously defined in main file, we keep it here:
        // It returns all products for the drawer.
    }

    /**
     * Add custom product fields to the 'Linked Products' tab in WooCommerce product data.
     */
    public function owcs_add_product_options() {
        global $post;

        echo '<div class="options_group">';

        // Checkbox for enabling modal
        woocommerce_wp_checkbox(array(
            'id' => '_owcs_enable_modal',
            'label' => __('Enable related product drawer', 'open-wp-cross-selling'),
            'desc_tip' => true,
            'description' => __('Enable this to show the cross-selling modal when this product is added to cart.', 'open-wp-cross-selling')
        ));

        // Presets
        $presets = get_option('owcs_presets', array());
        if (!empty($presets)) {
            $selected_preset = get_post_meta($post->ID, '_owcs_preset', true);

            $options = array('' => __('Select a preset', 'open-wp-cross-selling'));
            foreach ($presets as $preset_id => $preset) {
                $options[$preset_id] = $preset['name'];
            }

            woocommerce_wp_select(array(
                'id' => '_owcs_preset',
                'label' => __('Cross-Selling Preset', 'open-wp-cross-selling'),
                'options' => $options,
                'value' => $selected_preset,
                'desc_tip' => true,
                'description' => __('Select a preset of products to show in the drawer. These products will be combined with the additional products selected below.', 'open-wp-cross-selling')
            ));
        }

        // Additional products
        $additional_products = get_post_meta($post->ID, '_owcs_additional_products', true);
        ?>
        <p class="form-field">
            <label for="_owcs_additional_products"><?php _e('Additional drawer products', 'open-wp-cross-selling'); ?></label>
            <select class="wc-product-search" multiple="multiple" style="width: 50%;"
                    id="_owcs_additional_products"
                    name="_owcs_additional_products[]"
                    data-placeholder="<?php esc_attr_e('Search for additional products&hellip;', 'open-wp-cross-selling'); ?>"
                    data-action="woocommerce_json_search_products_and_variations">
                <?php
                if (!empty($additional_products)) {
                    foreach ($additional_products as $product_id) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            echo '<option value="' . esc_attr($product_id) . '" selected="selected">' .
                                 wp_kses_post($product->get_formatted_name()) .
                                 '</option>';
                        }
                    }
                }
                ?>
            </select>
        </p>
        <?php

        echo '</div>';
    }

    /**
     * Save custom product fields when product meta is saved.
     *
     * @param int $post_id The product ID.
     */
    public function owcs_save_product_options($post_id) {
        // Save enable modal checkbox
        $enable_modal = isset($_POST['_owcs_enable_modal']) ? 'yes' : 'no';
        update_post_meta($post_id, '_owcs_enable_modal', $enable_modal);

        // Save selected preset
        if (isset($_POST['_owcs_preset'])) {
            update_post_meta($post_id, '_owcs_preset', sanitize_text_field($_POST['_owcs_preset']));
        }

        // Save additional products
        $additional_products = isset($_POST['_owcs_additional_products']) ? array_map('intval', $_POST['_owcs_additional_products']) : array();
        update_post_meta($post_id, '_owcs_additional_products', $additional_products);
    }

    /**
     * Save product preset and copy its products to the product meta if needed.
     *
     * @param int $post_id The product ID.
     */
    public function owcs_save_product_preset($post_id) {
        // Save the selected preset
        if (isset($_POST['_owcs_preset'])) {
            $preset_id = sanitize_text_field($_POST['_owcs_preset']);
            update_post_meta($post_id, '_owcs_preset', $preset_id);

            // If a preset is selected, copy its products
            if (!empty($preset_id)) {
                $presets = get_option('owcs_presets', array());
                if (isset($presets[$preset_id])) {
                    update_post_meta($post_id, '_owcs_modal_products', $presets[$preset_id]['products']);
                }
            }
        }

        // If manually selected products are provided, they override the preset
        if (isset($_POST['_owcs_modal_products'])) {
            $products = array_map('intval', (array) $_POST['_owcs_modal_products']);
            update_post_meta($post_id, '_owcs_modal_products', $products);
        }
    }

    /**
     * Get all products to display in the drawer for a given product.
     *
     * @param int $product_id The product ID.
     * @return array List of product IDs.
     */
    public static function owcs_get_all_drawer_products($product_id) {
        $all_products = array();

        // Get preset products
        $selected_preset = get_post_meta($product_id, '_owcs_preset', true);
        if (!empty($selected_preset)) {
            $presets = get_option('owcs_presets', array());
            if (isset($presets[$selected_preset]['products'])) {
                $all_products = $presets[$selected_preset]['products'];
            }
        }

        // Add additional products
        $additional_products = get_post_meta($product_id, '_owcs_additional_products', true);
        if (!empty($additional_products)) {
            $all_products = array_merge($all_products, $additional_products);
        }

        // Remove duplicates and empty values
        $all_products = array_unique(array_filter($all_products));

        return $all_products;
    }

}
