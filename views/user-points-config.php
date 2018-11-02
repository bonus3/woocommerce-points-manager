<?php global $wc_points; ?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="wc_points_settings">
        <section>
            <h2><?php _e('General settings', 'woocommerce-points-manager'); ?></h2>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="enable-points-manager"><?php _e('Enable point redemption', 'woocommerce-points-manager'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="enable_points_manager" value="1" id="enable-points-manager" <?php echo $wc_points->sys->is_enabled() ? 'checked' : ''; ?>>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('WooCommerce price option', 'woocommerce-points-manager'); ?></th>
                    <td>
                        <?php $price_or_points = $wc_points->sys->get_price_option(); ?>
                        <div>
                            <input type="radio" name="price_or_points" value="only_prices" id="radio-only-prices" <?php echo $price_or_points === 'only_prices' ? 'checked' : ''; ?>>
                            <label for="radio-only-prices"><?php _e('Show currency value', 'woocommerce-points-manager'); ?></label>
                        </div>
                        <div>
                            <input type="radio" name="price_or_points" value="only_points" id="radio-only-points" <?php echo $price_or_points === 'only_points' ? 'checked' : ''; ?>>
                            <label for="radio-only-points"><?php _e('Show points value', 'woocommerce-points-manager'); ?></label>
                        </div>
                        <div>
                            <input type="radio" name="price_or_points" value="prices_points" id="radio-points-prices" <?php echo !in_array($price_or_points, ['only_prices', 'only_points']) ? 'checked' : ''; ?>>
                            <label for="radio-points-prices"><?php _e('Show currency and points values', 'woocommerce-points-manager'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="minimum-points">
                            <?php _e('Minimum of points to use', 'woocommerce-points-manager'); ?><br>
                            <small><?php _e('(in percent or absolute points)', 'woocommerce-points-manager'); ?></small>
                            <br>
                        </label>
                        <small><?php _e('Ex: 99.99% or 999.99', 'woocommerce-points-manager'); ?></small>
                    </th>
                    <td>
                        <input type="text" name="minimum_points" id="minimum-points" value="<?php esc_attr_e($wc_points->sys->get_minimum_points()); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="maximum-points">
                            <?php _e('Maximum of points to use', 'woocommerce-points-manager'); ?><br>
                            <small><?php _e('(in percent or absolute points. Set zero to no limit)', 'woocommerce-points-manager'); ?></small>
                            <br>
                        </label>
                        <small><?php _e('Ex: 99.99% or 999.99', 'woocommerce-points-manager'); ?></small>
                    </th>
                    <td>
                        <input type="text" name="maximum_points" id="maximum-points" value="<?php esc_attr_e($wc_points->sys->get_maximum_points()); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="redemption-only-points"><?php _e('Redemption only with points', 'woocommerce-points-manager'); ?></label><br>
                        <small><?php _e('(maximum and minimum will be ignored)', 'woocommerce-points-manager'); ?></small>
                        <br>
                    </th>
                    <td>
                        <input type="checkbox" name="redemption_only_points" value="1" id="redemption-only-points" <?php echo $wc_points->sys->is_only_points() ? 'checked' : ''; ?>>
                    </td>
                </tr>
            </table>
        </section>
        
        <section>
            <h2><?php _e('Profile points factor', 'woocommerce-points-manager'); ?></h2>
            <p><?php _e('Point factor is the multiplier applied to the price when converting the value into points.', 'woocommerce-points-manager'); ?></p>
            <table class="form-table">
                <tr>
                    <th>
                        <?php _e('Apply factor', 'woocommerce-points-manager'); ?>
                        <small><?php _e('(if user has more than one profile with different factors)', 'woocommerce-points-manager'); ?></small>
                    </th>
                    <td>
                        <?php $apply_factor = $wc_points->sys->get_type_apply_factor(); ?>
                        <div>
                            <input type="radio" name="apply_factor" value="min" id="radio-apply-min-factor" <?php echo esc_html($apply_factor) === 'min' ? 'checked' : ''; ?>>
                            <label for="radio-apply-min-factor"><?php _e('Minimum', 'woocommerce-points-manager'); ?></label>

                            <input type="radio" name="apply_factor" value="max" id="radio-apply-max-factor" <?php echo esc_html($apply_factor) !== 'min' ? 'checked' : ''; ?>>
                            <label for="radio-apply-max-factor"><?php _e('Maximium', 'woocommerce-points-manager'); ?></label>
                        </div>
                    </td>
                </tr>
            </table>
            <table class="form-table">
                <thead>
                    <tr>
                        <th><?php _e('Profile', 'woocommerce-points-manager'); ?></th>
                        <th>
                            <?php _e('Points factor', 'woocommerce-points-manager'); ?><br>
                            <small><?php _e('Ex: 999.9999', 'woocommerce-points-manager'); ?></small>
                        </th>
                        <th>
                            <?php _e('Points expiration', 'woocommerce-points-manager'); ?><br>
                            <small><?php _e('Ex: In days', 'woocommerce-points-manager'); ?></small>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wc_points->sys->get_roles() as $role => $data) : ?>
                    <tr>
                        <th>
                            <label for="wc-points-role-<?php echo $role; ?>"><?php echo esc_html($data['name']); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wc_points_role_<?php echo esc_html($role); ?>" id="wc-points-role-<?php echo esc_attr($role); ?>" value="<?php esc_attr_e($data['factor']); ?>">
                        </td>
                        <td>
                            <input type="text" name="wc_points_expiration_<?php echo esc_html($role); ?>" id="wc-points-expiration-<?php echo esc_attr($role); ?>" value="<?php esc_attr_e($data['expiration']); ?>" class="wc-points-profile-expiration">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php
            wp_nonce_field('wc-points-setting', 'wc_points_settings');
            submit_button();
        ?>
    </form>
</div>