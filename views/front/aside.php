<div class="owcs-backdrop" data-opened="true">
    <aside id="owcs-drawer" class="owcs-drawer">
        <div class="owcs-drawer-header">
            <h3>An amazing product has been added to cart</h3>

            <div id="owcs-close-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </div>
        </div>

        <div class="owcs-separator"></div>

        <div class="owcs-drawer-content">
            <h4>You may also like...</h4>

            <?php for($i = 0; $i < 4; $i++) { ?>
                <div class="owcs-product-container">
                    <img src="<?php echo esc_url(plugins_url('assets/img/200x200.png', OWCS_FILE)); ?>" alt="">

                    <div class="owcs-product-content">
                        <p class="owcs-product-title">A cool product</p>
                        <!-- <p class="owcs-product-desc">Lorem ipsum dolor sit amet...</p> -->
                        <button class="wp-element-button owcs-product-button">Add to cart</button> 
                    </div>
                </div>
            <?php }; ?>

        <div class="owcs-drawer-footer">
            <a class="wp-element-button" href="<?php echo esc_url( wc_get_cart_url() ); ?>">View cart</a>
        </div>
    </aside>
</div>