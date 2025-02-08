<?php

class TX_Badges_Activator {
    public static function activate() {
        global $wpdb;
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

        // Insert default settings for each default group
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

                $db_value = is_bool($value) ? ($value ? '1' : '0') : 
                          (is_array($value) ? wp_json_encode($value) : $value);

                if ($existing) {
                    $wpdb->update(
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
                } else {
                    $wpdb->insert(
                        $settings_table,
                        [
                            'group_id' => $group['id'],
                            'setting_name' => $name,
                            'setting_value' => $db_value,
                            'is_active' => 1
                        ],
                        ['%s', '%s', '%s', '%d']
                    );
                }
            }
        }
    }
}