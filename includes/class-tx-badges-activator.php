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
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_name (setting_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($settings_sql);

        // Insert default settings
        $default_settings = [
            'display_position' => 'after_add_to_cart',
            'mobile_display' => 'enabled',
            'animation_style' => 'fade',
            'badge_size' => 'medium',
            'layout_style' => 'horizontal'
        ];

        foreach ($default_settings as $name => $value) {
            $wpdb->replace(
                $settings_table,
                [
                    'setting_name' => $name,
                    'setting_value' => $value
                ],
                ['%s', '%s']
            );
        }
    }
}
