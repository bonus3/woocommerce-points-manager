<div>
    <p>
        <b><?php _e('Redeemed points', 'woocommerce-points-manager'); ?>:</b> 
        <?php echo $redeemed_points . apply_filters('wc_points_label', ' PTS'); ?>
    </p>
    <p>
        <b><?php _e('Points conversion factor', 'woocommerce-points-manager'); ?>:</b> 
        <?php echo $conversion_factor; ?>
    </p>
    <p>
        <b><?php _e('Customer current points', 'woocommerce-points-manager'); ?>:</b> 
        <?php echo $current_points; ?>
    </p>
</div>