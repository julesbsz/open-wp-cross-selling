<?php
$product_id = get_the_ID();
$enable_modal = get_post_meta($product_id, '_owcs_enable_modal', true);
$modal_products = OWCS_Product_Data::owcs_get_all_drawer_products($product_id);

// Si le modal n'est pas activé, on ne l'affiche pas
if ($enable_modal !== 'no') {
?>

<div class="owcs-backdrop" data-opened="false">
    <aside id="owcs-drawer" class="owcs-drawer">
        <div class="owcs-drawer-header">
            <h3>
            <?php 
                $added_product_name = isset($_COOKIE['owcs_added_product_name']) ? urldecode($_COOKIE['owcs_added_product_name']) : '';
                if ($added_product_name) {
                    echo sprintf(
                        esc_html__('%s has been added to cart', 'open-wp-cross-selling'),
                        esc_html($added_product_name)
                    );
                } else {
                    esc_html_e('Product has been added to cart', 'open-wp-cross-selling');
                }
            ?>
            </h3>
            <div id="owcs-close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </div>
        </div>

        <div class="owcs-separator"></div>

        <div class="owcs-drawer-content">
            <?php
                if (!empty($modal_products)) {
                    echo '<h4>' . esc_html__('You might also like...', 'open-wp-cross-selling') . '</h4>';

                    foreach ($modal_products as $product_id) {
                        $product = wc_get_product($product_id);
                        if (!$product) continue;
                        ?>
                        <div class="owcs-product-container" data-link="<?php echo esc_url($product->get_permalink()); ?>">
                            <?php echo $product->get_image('woocommerce_thumbnail'); ?>

                            <div class="owcs-product-content">
                                <p class="owcs-product-title"><?php echo esc_html($product->get_name()); ?> (<span class="owcs-product-price"><?php echo $product->get_price_html(); ?></span>)</p>
                                <p class="owcs-product-desc">
                                    <?php 
                                        $description = $product->get_short_description();
                                        echo wp_trim_words($description, 10, '...'); 
                                    ?> 
                                </p>
                                <button class="wp-element-button owcs-product-button-secondary" data-product-id="<?php echo esc_attr($product_id); ?>" data-nonce="<?php echo wp_create_nonce('wc-ajax-add-to-cart'); ?>">
                                    <?php esc_html_e('Add to cart', 'open-wp-cross-selling'); ?>
                                </button> 
                            </div>
                        </div>
                        <?php
                    }
                }
            ?>
        </div>

        <div class="owcs-drawer-footer">
            <a class="wp-element-button" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php esc_html_e('View cart', 'open-wp-cross-selling'); ?>
            </a>
        </div>
    </aside>
</div>

<?php }; ?>