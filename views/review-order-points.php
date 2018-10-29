<?php global $wc_points; ?>
<tr>
    <th><?php _e('Points to redeem', 'woocommerce-points-manager'); ?></th>
    <td><?php echo $wc_points->number_format(WC()->session->get('wc_points_to_redeem', 0)) . apply_filters('wc_points_label', ' PTS')?></td>
</tr>
    