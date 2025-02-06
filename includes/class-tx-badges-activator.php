<?php

class TX_Badges_Activator {
    public static function activate() {
        global $wpdb;
        
        try {
            $charset_collate = $wpdb->get_charset_collate();

            // Create badges table
            $table_name = $wpdb->prefix . 'tx_badges';
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                image_url text NOT NULL,
                link_url text,
                position int(11) DEFAULT 0,
                status varchar(20) DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            // Create settings table with group support
            $settings_table = $wpdb->prefix . 'tx_badges_settings';
            $settings_sql = "CREATE TABLE IF NOT EXISTS $settings_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                group_id varchar(50) NOT NULL DEFAULT 'default',
                setting_name varchar(255) NOT NULL,
                setting_value longtext NOT NULL,
                is_active tinyint(1) DEFAULT 1,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY group_setting (group_id, setting_name)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            // Suppress any output from dbDelta
            ob_start();
            $result = dbDelta($sql);
            if (!empty($wpdb->last_error)) {
                throw new Exception('Failed to create badges table: ' . $wpdb->last_error);
            }
            ob_end_clean();

            ob_start();
            $result = dbDelta($settings_sql);
            if (!empty($wpdb->last_error)) {
                throw new Exception('Failed to create settings table: ' . $wpdb->last_error);
            }
            ob_end_clean();

            // Default settings array
            $default_groups = [
                [
                    'id' => 'default',
                    'settings' => [
                        'show_header' => true,
                        'header_text' => 'Secure Checkout With',
                        'font_size' => '18',
                        'alignment' => 'center',
                        'badge_alignment' => 'center',
                        'text_color' => '#000000',
                        'badge_style' => 'original',
                        'badge_size_desktop' => 'medium',
                        'badge_size_mobile' => 'small',
                        'badge_color' => '#0066FF',
                        'custom_margin' => false,
                        'margin_top' => '0',
                        'margin_bottom' => '0',
                        'margin_left' => '0',
                        'margin_right' => '0',
                        'animation' => 'fade',
                        'show_on_product_page' => true,
                        'selected_badges' => ['mastercard', 'visa-1', 'paypal-1', 'apple-pay', 'stripe', 'american-express-1']
                    ]
                ],
                [
                    'id' => 'header',
                    'settings' => [
                        'show_header' => true,
                        'header_text' => 'Secure Payment Methods',
                        'font_size' => '18',
                        'alignment' => 'left',
                        'badge_alignment' => 'center',
                        'text_color' => '#000000',
                        'badge_style' => 'original',
                        'badge_size_desktop' => 'medium',
                        'badge_size_mobile' => 'small',
                        'badge_color' => '#0066FF',
                        'custom_margin' => false,
                        'margin_top' => '0',
                        'margin_bottom' => '0',
                        'margin_left' => '0',
                        'margin_right' => '0',
                        'animation' => 'fade',
                        'show_on_product_page' => true,
                        'selected_badges' => ['mastercard', 'visa-1', 'paypal-1', 'apple-pay', 'stripe', 'american-express-1']
                    ]
                ],
                [
                    'id' => 'footer',
                    'settings' => [
                        'show_header' => true,
                        'header_text' => 'Payment Options',
                        'font_size' => '18',
                        'alignment' => 'right',
                        'badge_alignment' => 'center',
                        'text_color' => '#000000',
                        'badge_style' => 'original',
                        'badge_size_desktop' => 'medium',
                        'badge_size_mobile' => 'small',
                        'badge_color' => '#0066FF',
                        'custom_margin' => false,
                        'margin_top' => '0',
                        'margin_bottom' => '0',
                        'margin_left' => '0',
                        'margin_right' => '0',
                        'animation' => 'fade',
                        'show_on_product_page' => true,
                        'selected_badges' => ['mastercard', 'visa-1', 'paypal-1', 'apple-pay', 'stripe', 'american-express-1']
                    ]
                ]
            ];

            foreach ($default_groups as $group) {
                foreach ($group['settings'] as $name => $value) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM $settings_table WHERE setting_name = %s AND group_id = %s",
                        $name,
                        $group['id']
                    ));

                    if ($wpdb->last_error) {
                        throw new Exception('Failed to check existing settings: ' . $wpdb->last_error);
                    }

                    $db_value = is_bool($value) ? ($value ? '1' : '0') : 
                              (is_array($value) ? wp_json_encode($value) : $value);

                    if ($existing) {
                        $update_result = $wpdb->update(
                            $settings_table,
                            [
                                'setting_value' => $db_value,
                                'is_active' => 1
                            ],
                            [
                                'setting_name' => $name,
                                'group_id' => $group['id']
                            ],
                            ['%s', '%d'],
                            ['%s', '%s']
                        );
                        
                        if ($update_result === false) {
                            throw new Exception('Failed to update settings: ' . $wpdb->last_error);
                        }
                    } else {
                        $insert_result = $wpdb->insert(
                            $settings_table,
                            [
                                'group_id' => $group['id'],
                                'setting_name' => $name,
                                'setting_value' => $db_value,
                                'is_active' => 1
                            ],
                            ['%s', '%s', '%s', '%d']
                        );
                        
                        if ($insert_result === false) {
                            throw new Exception('Failed to insert settings: ' . $wpdb->last_error);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Log the error and rethrow
            error_log('TX Badges Activation Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
