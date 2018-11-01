<div id="wc-points-thickbox" style="display:none;">
    <p class="wc-points-loading"><?php _e('Loading...', 'woocommerce-points-manager'); ?></p>
    <div id="wc-points-tabs" class="tabs wc-points-content">
        <ul>
            <li><a href="#wc-points-extract"><?php _e('Point extract', 'woocommerce-points-manager'); ?></a></li>
            <li><a href="#wc-points-points-adjustment"><?php _e('Operations', 'woocommerce-points-manager'); ?></a></li>
        </ul>
        <div id="wc-points-extract">
            <div>
                <span class="displaying-num"></span>
                <a class="prev-page wc-points-navigation" href="#">&lt;</a>
                <a class="next-page wc-points-navigation" href="#">&gt;</a>
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
                <a class="prev-page wc-points-navigation" href="#">&lt;</a>
                <a class="next-page wc-points-navigation" href="#">&gt;</a>
            </div>
        </div>
        <div id="wc-points-points-adjustment">
            <form id="wc-points-operations" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" class="wc-points-form" method="post">
                <input type="hidden" name="action" value="wc_points_operations">
                <input type="hidden" name="user_id" value="" id="wc-operation-user">
                <table class="table">
                    <tr>
                        <th>
                            <?php _e('Current points', 'woocommerce-points-manager'); ?>
                        </th>
                        <td id="wc-points-user-current-points"></td>
                    </tr>
                    <tr>
                        <th>
                            <?php _e('Conversion factor', 'woocommerce-points-manager'); ?>
                        </th>
                        <td id="wc-points-user-factor-conversion"></td>
                    </tr>
                    <tr>
                        <th>
                            <label for="balance-adjustment"><?php _e('Balance adjustment', 'woocommerce-points-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="balance_adjustment" id="balance-adjustment"><br>
                            <small><?php _e('Positive or negative values', 'woocommerce-points-manager'); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="balance-adjustment-description"><?php _e('Description', 'woocommerce-points-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="balance_adjustment_description" id="balance-adjustment-description">
                        </td>
                    </tr>
                </table>
                <?php
                wp_nonce_field('wc-points-operations', 'wc_points_operations');
                submit_button();
                ?>
            </form>
        </div>
    </div>
</div>