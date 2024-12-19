<?php 

class OWCS_Settings {
    public function __construct() {
        // Ajouter le menu principal dans WooCommerce
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_cross_selling_drawer', array($this, 'settings_tab'));
        
        // Gérer les actions AJAX
        add_action('wp_ajax_owcs_delete_preset', array($this, 'delete_preset'));
        add_action('wp_ajax_owcs_save_preset', array($this, 'save_preset'));
        add_action('wp_ajax_owcs_update_preset', array($this, 'update_preset'));

        // Ajouter les styles et scripts d'admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_settings_tab($settings_tabs) {
        $settings_tabs['cross_selling_drawer'] = __('Cross Selling Drawer', 'open-wp-cross-selling');
        return $settings_tabs;
    }

    public function settings_tab() {
        // Gestion de l'onglet actif
        $current_tab = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'create';
        
        // En-tête avec les sous-onglets
        echo '<div class="wrap">';
        echo '<ul class="subsubsub">';
        
        $tabs = array(
            'create' => __('Create Preset', 'open-wp-cross-selling'),
            'list' => __('Manage Presets', 'open-wp-cross-selling')
        );

        foreach ($tabs as $tab_id => $tab_name) {
            $active = $current_tab === $tab_id ? 'current' : '';

            if ($tab_id == "list") {
                echo sprintf(
                    '<li><a href="?page=wc-settings&tab=cross_selling_drawer&section=%s" class="%s">%s</a></li>',
                    $tab_id,
                    $active,
                    esc_html($tab_name)
                );
            } else {
                echo sprintf(
                    '<li><a href="?page=wc-settings&tab=cross_selling_drawer&section=%s" class="%s">%s</a></li> | ',
                    $tab_id,
                    $active,
                    esc_html($tab_name)
                );
            }
            
        }
        
        echo '</ul><br />';

        // Afficher le contenu en fonction de l'onglet actif
        if ($current_tab === 'create') {
            $this->render_create_preset_page();
        } else {
            $this->render_list_presets_page();
        }

        echo '</div>';
    }

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

    public function enqueue_admin_assets($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) {
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
    
        // Données pour le JavaScript
        wp_localize_script('owcs-admin-script', 'owcsAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('owcs_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this preset?', 'open-wp-cross-selling'),
                'presetSaved' => __('Preset saved successfully!', 'open-wp-cross-selling'),
                'error' => __('An error occurred. Please try again.', 'open-wp-cross-selling'),
            )
        ));
    
        // Ajout pour déboguer
        ?>
        <script>
            console.log('owcsAdmin:', <?php echo json_encode(array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('owcs_admin_nonce'),
                'strings' => array(
                    'confirmDelete' => __('Are you sure you want to delete this preset?', 'open-wp-cross-selling'),
                    'presetSaved' => __('Preset saved successfully!', 'open-wp-cross-selling'),
                    'error' => __('An error occurred. Please try again.', 'open-wp-cross-selling'),
                )
            )); ?>);
        </script>
        <?php
    }

    /**
     * Sauvegarde les presets en fonction des données du formulaire de réglages.
     */
    public function save_preset() {
        // Vérification du nonce
        check_ajax_referer('owcs_admin_nonce', 'nonce');
    
        // Vérification des droits
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
            return;
        }
    
        // Récupération et validation des données
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $products = isset($_POST['products']) ? array_map('absint', (array) $_POST['products']) : array();
    
        if (empty($name)) {
            wp_send_json_error('Preset name is required');
            return;
        }
    
        // Récupération des presets existants
        $presets = get_option('owcs_presets', array());
    
        // Création du nouveau preset
        $preset_id = uniqid();
        $presets[$preset_id] = array(
            'name' => $name,
            'products' => $products
        );
    
        // Sauvegarde
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
     * Action AJAX pour supprimer un preset.
     * Vérifie le nonce, supprime le preset et renvoie une réponse JSON.
     */
    public function delete_preset() {
        // Vérification de sécurité (nonce)
        check_ajax_referer('delete_preset_' . $_POST['preset_id'], 'nonce');

        $preset_id = sanitize_text_field($_POST['preset_id']);
        $presets   = get_option('owcs_presets', array());

        // Suppression du preset s'il existe
        if (isset($presets[$preset_id])) {
            unset($presets[$preset_id]);
            update_option('owcs_presets', $presets);
            wp_send_json_success();
        }

        wp_send_json_error();
    }

    public function update_preset() {
        try {
            // Vérification du nonce
            check_ajax_referer('owcs_admin_nonce', 'nonce');
    
            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error('Permission denied');
                return;
            }
    
            // Debug log
            error_log('Received update_preset request: ' . print_r($_POST, true));
    
            $preset_id = sanitize_text_field($_POST['preset_id']);
            $products = isset($_POST['products']) ? array_map('absint', (array) $_POST['products']) : array();
    
            // Récupération des presets existants
            $presets = get_option('owcs_presets', array());
            error_log('Current presets: ' . print_r($presets, true));
    
            if (!isset($presets[$preset_id])) {
                wp_send_json_error('Preset not found: ' . $preset_id);
                return;
            }
    
            // Mise à jour du preset
            $old_preset = $presets[$preset_id];
            $presets[$preset_id] = array(
                'name' => $old_preset['name'],
                'products' => $products
            );
    
            error_log('Updated presets before save: ' . print_r($presets, true));
    
            // Mise à jour de l'option
            $update_result = update_option('owcs_presets', $presets);
            error_log('Update result: ' . ($update_result ? 'success' : 'failed'));
    
            if ($update_result) {
                wp_send_json_success(array(
                    'message' => 'Preset updated successfully',
                    'preset_id' => $preset_id,
                    'products' => $products
                ));
            } else {
                // Vérifions si les données ont quand même été mises à jour
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

new OWCS_Settings();