<?php global $wc_points; ?>
<tr>
    <th>
        <?php _e('Apply points', 'woocommerce-points-manager') ?><br>
        <small><?php _e('Current points', 'woocommerce-points-manager') ?>: <?php echo do_shortcode('[wc_points_user_points]'); ?></small>
    </th>
    <td>
        <form action="" method="post" class="wc-points-form">
            <div>
                <input type="range" name="wc_points_to_cash" id="wc-points-cash" min="0" max="<?php echo WC()->cart->get_cart_contents_total() + WC()->cart->get_shipping_total(); ?>" step="0.01" value="<?php echo WC()->session->get('wc_points_to_cash', 0); ?>">
            </div>
            <div>
                <p>
                    <?php _e('Value in points', 'woocommerce-points-manager') ?>: <b id="wc-points-to-redeem">0,00</b>
                </p>
                <p>
                    <?php _e('Cash value', 'woocommerce-points-manager') ?>: <b id="wc-points-to-cash">0,00</b>
                </p>
            </div>
            <input type="submit" value="Aplicar">
        </form>
    </td>
</tr>