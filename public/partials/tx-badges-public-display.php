<?php
/**
 * Provide a public-facing view for the plugin
 */

global $wpdb;
$table_name = $wpdb->prefix . 'tx_badges';
$badges = $wpdb->get_results("SELECT * FROM {$table_name} WHERE status = 'active' ORDER BY position ASC");

if (!empty($badges)): ?>
<div class="tx-badges-container">
    <?php foreach ($badges as $badge): ?>
        <div class="tx-badge">
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
