<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the WooCommerce settings tab and preset management (create, list, update, delete).
 * 
 * Extracted from `woocommerce-settings.php`.
 */
class OWCS_Settings {

    /**
     * Initialize settings-related hooks.
     */
    public function init() {
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_cross_selling_drawer', array($this, 'settings_tab'));

        // AJAX actions for presets
        add_action('wp_ajax_owcs_delete_preset', array($this, 'delete_preset'));
        add_action('wp_ajax_owcs_save_preset', array($this, 'save_preset'));
        add_action('wp_ajax_owcs_update_preset', array($this, 'update_preset'));
    }

    /**
     * Add a new WooCommerce settings tab for the cross-selling drawer.
     *
     * @param array $settings_tabs Existing tabs.
     * @return array Modified tabs.
     */
    public function add_settings_tab($settings_tabs) {
        $settings_tabs['cross_selling_drawer'] = __('Cross Selling Drawer', 'open-wp-cross-selling');
        return $settings_tabs;
    }

    /**
     * Render the settings tab content.
     */
    public function settings_tab() {
        $current_tab = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'create';

        echo '<div class="wrap">';
        echo '<ul class="subsubsub">';

        $tabs = array(
            'create' => __('Create Preset', 'open-wp-cross-selling'),
            'list' => __('Manage Presets', 'open-wp-cross-selling')
        );

        $i = 0;
        foreach ($tabs as $tab_id => $tab_name) {
            $active = $current_tab === $tab_id ? 'current' : '';
            echo sprintf(
                '<li><a href="?page=wc-settings&tab=cross_selling_drawer&section=%s" class="%s">%s</a>%s</li>',
                $tab_id,
                $active,
                esc_html($tab_name),
                ($i == 0 ? ' | ' : '')
            );
            $i++;
        }

        echo '</ul><br />';

        if ($current_tab === 'create') {
            $this->render_create_preset_page();
        } else {
            $this->render_list_presets_page();
        }

        echo '</div>';
    }

    /**
     * Render the page for creating a new preset.
     */
    private function render_create_preset_page() {
        ?>
        <div class="owcs-create-preset">
            <h2><?php _e('Create New Preset', 'open-wp-cross-selling'); ?></h2>

            <form id="owcs-create-preset-form" class="owcs-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="preset_name"><?php _e('Preset Name', 'open-wp-cross-selling'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="preset_name" name="preset_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="preset_products"><?php _e('Select Products', 'open-wp-cross-selling'); ?></label>
                        </th>
                        <td>
                            <select name="preset_products[]" id="preset_products" class="wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e('Search for a product&hellip;', 'open-wp-cross-selling'); ?>">
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     * Render the page for listing and managing existing presets.
     */
    private function render_list_presets_page() {
        $presets = get_option('owcs_presets', array());
        ?>
        <div class="owcs-list-presets">
            <h2><?php _e('Manage Presets', 'open-wp-cross-selling'); ?></h2>

            <div class="owcs-presets-container">
                <?php if (empty($presets)): ?>
                    <p><?php _e('No presets found.', 'open-wp-cross-selling'); ?></p>
                <?php else: ?>
                    <?php foreach ($presets as $preset_id => $preset): ?>
                        <div class="owcs-preset-card" data-preset-id="<?php echo esc_attr($preset_id); ?>">
                            <div class="owcs-preset-header">
                                <h3><?php echo esc_html($preset['name']); ?></h3>
                                <div class="owcs-preset-actions">
                                    <button type="button" class="button button-link-delete owcs-delete-preset" data-preset-id="<?php echo esc_attr($preset_id); ?>" data-nonce="<?php echo wp_create_nonce('delete_preset_' . $preset_id); ?>">
                                        <?php _e('Delete', 'open-wp-cross-selling'); ?>
                                    </button>
                                </div>
                            </div>

                            <div class="owcs-preset-content">
                                <select name="preset_products[]" class="wc-product-search" multiple="multiple" data-placeholder="<?php esc_attr_e('Search for products&hellip;', 'open-wp-cross-selling'); ?>">
                                    <?php
                                    if (!empty($preset['products'])) {
                                        foreach ($preset['products'] as $product_id) {
                                            $product = wc_get_product($product_id);
                                            if ($product) {
                                                printf(
                                                    '<option value="%s" selected="selected">%s</option>',
                                                    esc_attr($product_id),
                                                    wp_kses_post($product->get_formatted_name())
                                                );
                                            }
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Save a new preset.
     */
    public function save_preset() {
        check_ajax_referer('owcs_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $products = isset($_POST['products']) ? array_map('absint', (array) $_POST['products']) : array();

        if (empty($name)) {
            wp_send_json_error('Preset name is required');
            return;
        }

        $presets = get_option('owcs_presets', array());

        $preset_id = uniqid();
        $presets[$preset_id] = array(
            'name' => $name,
            'products' => $products
        );

        if (update_option('owcs_presets', $presets)) {
            wp_send_json_success(array(
                'message' => __('Preset created successfully', 'open-wp-cross-selling'),
                'preset_id' => $preset_id
            ));
        } else {
            wp_send_json_error('Failed to save preset');
        }
    }

    /**
     * AJAX: Delete a preset.
     */
    public function delete_preset() {
        check_ajax_referer('delete_preset_' . $_POST['preset_id'], 'nonce');

        $preset_id = sanitize_text_field($_POST['preset_id']);
        $presets   = get_option('owcs_presets', array());

        if (isset($presets[$preset_id])) {
            unset($presets[$preset_id]);
            update_option('owcs_presets', $presets);
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    /**
     * AJAX: Update an existing preset.
     */
    public function update_preset() {
        try {
            check_ajax_referer('owcs_admin_nonce', 'nonce');

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('Permission denied');
                return;
            }

            $preset_id = sanitize_text_field($_POST['preset_id']);
            $products = isset($_POST['products']) ? array_map('absint', (array) $_POST['products']) : array();

            $presets = get_option('owcs_presets', array());

            if (!isset($presets[$preset_id])) {
                wp_send_json_error('Preset not found: ' . $preset_id);
                return;
            }

            $old_preset = $presets[$preset_id];
            $presets[$preset_id] = array(
                'name' => $old_preset['name'],
                'products' => $products
            );

            $update_result = update_option('owcs_presets', $presets);

            if ($update_result) {
                wp_send_json_success(array(
                    'message' => 'Preset updated successfully',
                    'preset_id' => $preset_id,
                    'products' => $products
                ));
            } else {
                // Double check if updated
                $after_update = get_option('owcs_presets');
                if ($after_update[$preset_id]['products'] === $products) {
                    wp_send_json_success(array(
                        'message' => 'Preset updated successfully (verified)',
                        'preset_id' => $preset_id,
                        'products' => $products
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => 'Failed to update preset',
                        'preset_id' => $preset_id,
                        'old_products' => $old_preset['products'],
                        'new_products' => $products,
                        'actual_products' => $after_update[$preset_id]['products']
                    ));
                }
            }

        } catch (Exception $e) {
            error_log('Error in update_preset: ' . $e->getMessage());
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }

}
