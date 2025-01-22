<?php
/**
 * Provide a public-facing view for the plugin
 */

$settings = get_option('tx_trust_badges_settings', array(
    'enabled' => false,
    'showHeader' => true,
    'headerText' => 'Secure Checkout With',
    'font' => 'Asap',
    'fontSize' => '18',
    'alignment' => 'center',
    'textColor' => '#000000',
    'badgeStyle' => 'original',
    'badgeSizeDesktop' => 'medium',
    'badgeSizeMobile' => 'small',
    'badgeColor' => '#0066FF',
    'customMargin' => false,
    'marginTop' => '0',
    'marginBottom' => '0',
    'animation' => 'fade',
    'showOnProductPage' => true,
    'selectedBadges' => array()
));

global $wpdb;
$table_name = $wpdb->prefix . 'tx_badges';
$badges = $wpdb->get_results("SELECT * FROM {$table_name} WHERE status = 'active' ORDER BY position ASC");

if (!empty($badges)): ?>
<div class="tx-badges-container">
    <?php foreach ($badges as $badge): ?>
        <div class="tx-badge" 
             data-desktop-size="<?php echo esc_attr($settings['badgeSizeDesktop']); ?>"
             data-mobile-size="<?php echo esc_attr($settings['badgeSizeMobile']); ?>">
            <?php if (!empty($badge->link_url)): ?>
                <a href="<?php echo esc_url($badge->link_url); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   title="<?php echo esc_attr($badge->title); ?>">
                    <img src="<?php echo esc_url($badge->image_url); ?>" 
                         alt="<?php echo esc_attr($badge->title); ?>"
                         loading="lazy" />
                </a>
            <?php else: ?>
                <img src="<?php echo esc_url($badge->image_url); ?>" 
                     alt="<?php echo esc_attr($badge->title); ?>"
                     loading="lazy" />
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
