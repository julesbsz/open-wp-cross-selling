<?php

add_action('woocommerce_product_options_related', 'owcs_add_product_options');
function owcs_add_product_options() {
    global $post;

    echo '<div class="options_group">';

    // Checkbox pour activer le modal
    woocommerce_wp_checkbox(array(
        'id' => '_owcs_enable_modal',
        'label' => __('Enable related product drawer', 'open-wp-cross-selling'),
        'desc_tip' => true,
        'description' => __('Enable this to show the cross-selling modal when this product is added to cart.', 'open-wp-cross-selling')
    ));

    // Champ pour les produits
    $selected_products = get_post_meta($post->ID, '_owcs_modal_products', true);
    ?>
    <p class="form-field">
        <label for="_owcs_modal_products"><?php _e('Modal products', 'open-wp-cross-selling'); ?></label>
        <select class="wc-product-search" multiple="multiple" style="width: 50%;" 
                id="_owcs_modal_products" 
                name="_owcs_modal_products[]" 
                data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'open-wp-cross-selling'); ?>"
                data-action="woocommerce_json_search_products_and_variations">
            <?php
            if (!empty($selected_products)) {
                foreach ($selected_products as $product_id) {
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

// Sauvegarde des données
add_action('woocommerce_process_product_meta', 'owcs_save_product_options');
function owcs_save_product_options($post_id) {
    // Sauvegarde du checkbox
    $enable_modal = isset($_POST['_owcs_enable_modal']) ? 'yes' : 'no';
    update_post_meta($post_id, '_owcs_enable_modal', $enable_modal);

    // Sauvegarde des produits sélectionnés
    $modal_products = isset($_POST['_owcs_modal_products']) ? array_map('intval', $_POST['_owcs_modal_products']) : array();
    update_post_meta($post_id, '_owcs_modal_products', $modal_products);
}