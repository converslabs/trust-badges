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

        // Create settings table
        $settings_table = $wpdb->prefix . 'tx_badges_settings';
        $settings_sql = "CREATE TABLE IF NOT EXISTS $settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_name varchar(255) NOT NULL,
            setting_value longtext NOT NULL,
            setting_group varchar(50) DEFAULT 'general',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($settings_sql);

        // Insert or update default settings
        $default_settings = [
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
        ];

        foreach ($default_settings as $name => $value) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $settings_table WHERE setting_name = %s",
                $name
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
                    ['setting_name' => $name],
                    ['%s', '%d'],
                    ['%s']
                );
            } else {
                $wpdb->insert(
                    $settings_table,
                    [
                        'setting_name' => $name,
                        'setting_value' => $db_value,
                        'setting_group' => 'general',
                        'is_active' => 1
                    ],
                    ['%s', '%s', '%s', '%d']
                );
            }
        }
    }
}
