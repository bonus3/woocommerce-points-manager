<div id="wc-points-extract">
    <div>
        <span class="displaying-num"></span>
        <a class="prev-page wc-points-navigation" href="<?php echo esc_attr($previous_link); ?>">
            &lt; <?php _e('Before', 'woocommerce-points-manager'); ?>
        </a> - 
        <a class="next-page wc-points-navigation" href="<?php echo esc_attr($next_link); ?>">
            <?php _e('After', 'woocommerce-points-manager'); ?> &gt;
        </a>
    </div>
    <table class="table" border="1">
        <thead>
            <tr>
                <th><?php _e('Description', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Date', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Expiration', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Points', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Current points', 'woocommerce-points-manager'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($extract as $data) : ?>
            <tr>
                <td><?php echo $data->description; ?></td>
                <td><?php echo $data->entryFormated; ?></td>
                <td><?php echo isset($data->expiredFormated) ? $data->expiredFormated : '-'; ?></td>
                <td><?php echo $data->points; ?></td>
                <td><?php echo $data->current_points; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?php _e('Description', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Date', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Expiration', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Points', 'woocommerce-points-manager'); ?></th>
                <th><?php _e('Current points', 'woocommerce-points-manager'); ?></th>
            </tr>
        </tfoot>
    </table>
    <div>
        <span class="displaying-num"></span>
        <a class="prev-page wc-points-navigation" href="<?php echo esc_attr($previous_link); ?>">
            &lt; <?php _e('Before', 'woocommerce-points-manager'); ?>
        </a> - 
        <a class="next-page wc-points-navigation" href="<?php echo esc_attr($next_link); ?>">
            <?php _e('After', 'woocommerce-points-manager'); ?> &gt;
        </a>
    </div>
</div>