<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin initialization class.
 * 
 * Handles:
 * - Enqueuing admin scripts/styles
 * - Possibly other admin-wide actions
 */
class OWCS_Admin {

    /**
     * Initialize admin-related hooks.
     */
    public function init() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // The JS snippet for updating presets on product edit pages
        add_action('admin_footer', array($this, 'preset_script'));
    }

    /**
     * Enqueue admin styles and scripts on WooCommerce settings pages.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Load assets only on WooCommerce settings pages
        if ('woocommerce_page_wc-settings' !== $hook && 'post.php' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'owcs-admin-styles',
            plugins_url('assets/css/admin.css', OWCS_FILE),
            array(),
            OWCS_VERSION
        );

        wp_enqueue_script(
            'owcs-admin-script',
            plugins_url('assets/js/admin.js', OWCS_FILE),
            array('jquery'),
            OWCS_VERSION,
            true
        );

        wp_localize_script('owcs-admin-script', 'owcsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('owcs_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this preset?', 'open-wp-cross-selling'),
                'presetSaved' => __('Preset saved successfully!', 'open-wp-cross-selling'),
                'error' => __('An error occurred. Please try again.', 'open-wp-cross-selling'),
            )
        ));
    }

    /**
     * Inject inline JS for preset selection on product pages.
     */
    public function preset_script() {
        if (!isset($_GET['post']) || get_post_type($_GET['post']) !== 'product') {
            return;
        }
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
}
