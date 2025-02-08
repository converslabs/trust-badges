<?php

class TX_Badges_Activator {
    public static function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id varchar(50) NOT NULL,
            group_name varchar(100) NOT NULL,
            is_default tinyint(1) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            required_plugin varchar(50) DEFAULT NULL,
            settings longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY group_id (group_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert default settings if table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($count == 0) {
            $default_groups = [
                [
                    'group_id' => 'woocommerce',
                    'group_name' => 'WooCommerce',
                    'is_default' => 1,
                    'is_active' => 1,
                    'required_plugin' => 'woocommerce',
                    'settings' => json_encode([
                        'showHeader' => true,
                        'headerText' => 'Secure Checkout With',
                        'fontSize' => '18',
                        'alignment' => 'center',
                        'badgeAlignment' => 'center',
                        'textColor' => '#000000',
                        'badgeStyle' => 'original',
                        'badgeSizeDesktop' => 'medium',
                        'badgeSizeMobile' => 'small',
                        'badgeColor' => '#0066FF',
                        'customMargin' => false,
                        'marginTop' => '0',
                        'marginBottom' => '0',
                        'marginLeft' => '0',
                        'marginRight' => '0',
                        'animation' => 'fade',
                        'showAfterAddToCart' => false,
                        'showBeforeAddToCart' => false,
                        'showOnCheckout' => false,
                        'selectedBadges' => ['mastercard', 'visa-1', 'paypal-1', 'apple-pay', 'stripe', 'american-express-1']
                    ])
                ]
            ];

            foreach ($default_groups as $group) {
                $wpdb->insert($table_name, $group);
            }
        }
    }
}