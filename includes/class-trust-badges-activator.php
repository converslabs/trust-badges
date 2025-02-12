<?php

class TX_Badges_Activator {
    public static function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'converswp_trust_badges';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id varchar(50) NOT NULL,
            group_name varchar(255) NOT NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            required_plugin varchar(50) DEFAULT NULL,
            settings longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY group_id (group_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Insert default groups if they don't exist
        $default_groups = [
            [
                'group_id' => 'checkout',
                'group_name' => 'Checkout',
                'is_default' => 1,
                'is_active' => 1,
                'required_plugin' => 'woocommerce',
                'settings' => json_encode([
                    'showHeader' => true,
                    'headerText' => 'Secure Checkout With',
                    'fontSize' => '18',
                    'alignment' => 'left',
                    'badgeAlignment' => 'left',
                    'position' => 'center',
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
                    'checkoutBeforeOrderReview' => true,
                    'eddCheckoutBeforePurchaseForm' => true,
                    'selectedBadges' => [
                        'mastercardcolor',
                        'visa1color',
                        'paypal1color',
                        'applepaycolor',
                        'stripecolor',
                        'amazonpay2color',
                        'americanexpress1color'
                    ]
                ])
            ],
            [
                'group_id' => 'product_page',
                'group_name' => 'Product Page',
                'is_default' => 1,
                'is_active' => 1,
                'required_plugin' => 'woocommerce',
                'settings' => json_encode([
                    'showHeader' => true,
                    'headerText' => 'Secure Payment Methods',
                    'fontSize' => '18',
                    'alignment' => 'left',
                    'badgeAlignment' => 'center',
                    'position' => 'center',
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
                    'showAfterAddToCart' => true,
                    'eddPurchaseLinkEnd' => true,
                    'selectedBadges' => [
                        'mastercardcolor',
                        'visa1color',
                        'paypal1color'
                    ]
                ])
            ],
            [
                'group_id' => 'footer',
                'group_name' => 'Footer',
                'is_default' => 1,
                'is_active' => 1,
                'required_plugin' => null,
                'settings' => json_encode([
                    'showHeader' => true,
                    'headerText' => 'Payment Options',
                    'fontSize' => '18',
                    'alignment' => 'center',
                    'badgeAlignment' => 'center',
                    'position' => 'center',
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
                    'selectedBadges' => [
                        'mastercardcolor',
                        'visa1color',
                        'paypal1color'
                    ]
                ])
            ]
        ];

        foreach ($default_groups as $group) {
            $wpdb->replace(
                $table_name,
                $group,
                ['%s', '%s', '%d', '%d', '%s', '%s']
            );
        }
    }
}