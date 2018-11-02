<?php

namespace WooPoints;

class WooCommerce {
    
    public $discount = 0;
    
    public function __construct() {
        add_action('woocommerce_cart_totals_before_order_total', [$this, 'points_cart']);
        add_action('woocommerce_cart_calculate_fees',            [$this, 'aplly_points_cart']);
        add_filter('woocommerce_cart_needs_payment',             [$this, 'disable_payment_method']);
        add_action('woocommerce_checkout_update_order_meta',     [$this, 'create_transaction']);
        add_action('woocommerce_checkout_process',               [$this, 'check_points_to_redeem']);
        add_action('woocommerce_order_status_changed',           [$this, 'points_order_change'], 10, 4);
        add_filter('woocommerce_cart_totals_order_total_html',   [$this, 'cart_total_html']);
        add_filter('wc_price',                                   [$this, 'formatting_html'], 10, 4);
        add_filter('woocommerce_get_formatted_order_total',      [$this, 'formatted_order_total'], 10, 4);
        add_action('woocommerce_init',                           [$this, 'load_session']);
    }
    
    public function points_cart() {
        global $wc_points;
        if (!$wc_points->sys->is_only_points()) {
            include_once WC_POINTS_PATH . 'views/cart-points.php';
        }
    }
    
    public function disable_payment_method() {
        global $wc_points;
        return !$wc_points->sys->is_only_points();
    }
    
    public function aplly_points_cart($cart) {
        global $wc_points;
        $to_cash = 0;
        $total_cart = WC()->cart->get_cart_contents_total() + WC()->cart->get_shipping_total();
        if (!$wc_points->sys->is_only_points()) {
            if(!empty(WC()->session->get('wc_points_to_cash', 0))) {
                $to_cash = WC()->session->get('wc_points_to_cash', $wc_points->sys->calculate_max_points($total_cart));
            }
        } else {
            $to_cash = $total_cart;
            WC()->session->set('wc_points_to_cash', $to_cash);
        }
        try {
            $this->check_points_to_redeem();
            $cart->add_fee(__('Discount', 'woocommerce-points-manager'), -$to_cash, true);
        } catch (\Exception $e) {
            if (is_cart()) {
                wc_add_notice($e->getMessage(), 'error');
            }
        }
    }
    
    public function check_points_to_redeem() {
        global $wc_points;
        $user_factor = $wc_points->sys->get_current_user()->get_factor();
        $cash = WC()->cart->get_cart_contents_total() + WC()->cart->get_shipping_total();
        $cash_minimum = $wc_points->sys->is_only_points() ? 0 : round($wc_points->sys->calculate_min_points($cash), 2);
        $cash_maximum = $wc_points->sys->is_only_points() ? 0 : round($wc_points->sys->calculate_max_points($cash), 2);
        $to_cash = $wc_points->sys->is_only_points() ? $cash : WC()->session->get('wc_points_to_cash', $cash_minimum);
        $current_points = $wc_points->sys->get_current_user()->points->get_current_points();
        $cash -= $to_cash;
        $points_to_redeem = $to_cash * $user_factor;
        WC()->session->set('wc_points_to_redeem', $points_to_redeem);
        if ($points_to_redeem > $current_points) {
            WC()->session->set('wc_points_to_cash', 0);
            throw new \Exception(__('Insufficient points', 'woocommerce-points-manager'));
        }
        if ($to_cash < $cash_minimum) {
            WC()->session->set('wc_points_to_cash', $cash_minimum);
            throw new \Exception(sprintf(__('Minimal of points is insufficient. Minimum required: %s', 'woocommerce-points-manager'), $wc_points->number_format($cash_minimum * $user_factor)));
        }
        
        if ($cash_maximum > 0 && $to_cash > $cash_maximum) {
            WC()->session->set('wc_points_to_cash', $cash_maximum);
            throw new \Exception(sprintf(__('Maxim of points reached. Maximum required: %s', 'woocommerce-points-manager'), $wc_points->numer_format($cash_maximum * $user_factor)));
        }
        return true;
    }
    
    public function create_transaction($order_id) {
        global $wc_points;
        $order = wc_get_order($order_id);
        $points_to_redeem = 0;
        foreach ($order->get_items('fee') as $item_fee) {
            if ($item_fee->get_name() === __('Discount', 'woocommerce-points-manager')) {
                $points_to_redeem += $item_fee->get_total();
            }
        }
        $points_to_redeem *= $wc_points->sys->get_current_user()->get_factor();
        $points_to_redeem = apply_filters('wc_points_points_to_redeem', $points_to_redeem, $order);
        add_post_meta($order_id, '_redeemed_points', $points_to_redeem);
        add_post_meta($order_id, '_conversion_factor', $wc_points->sys->get_current_user()->get_factor());
        add_post_meta($order_id, '_customer_points_before', $wc_points->sys->get_current_user()->points->get_current_points());
        $wc_points->sys->get_current_user()->points->insert_transaction($points_to_redeem, 'order', $order_id, __('Redeemed points', 'woocommerce-points-manager'));
        WC()->session->set('wc_points_to_cash', 0);
    }
    
    public function points_order_change($order_id, $status_old, $status_new, $order) {
        global $wc_points;
        $customer = new User($order->get_customer_id());
        if (in_array($status_old, ['pending', 'processing', 'completed', 'on-hold'])
                && in_array($status_new, ['cancelled', 'failed', 'refunded'])
                && empty(get_post_meta($order_id, '_redeemed_points_reversal', true))) {
            $points = $wc_points->sys->calculate_points_to_new_factor(
                abs(get_post_meta($order_id, '_redeemed_points', true)),
                get_post_meta($order_id, '_conversion_factor', true),
                $customer->get_factor()
            );
            $customer->points->insert_transaction(
                $points, 'order-reverse', $order_id, __('Points reversal', 'woocommerce-points-manager')
            );
            update_post_meta($order_id, '_redeemed_points_reversal', true);
        } else if (in_array($status_new, ['pending', 'processing', 'completed', 'on-hold'])
                && in_array($status_old, ['cancelled', 'failed', 'refunded'])
                && !empty(get_post_meta($order_id, '_redeemed_points_reversal', true))) {
            $points_to_redeem = get_post_meta($order_id, '_redeemed_points', true);
            if ($customer->points->get_current_points() < abs($points_to_redeem)) {
                $order->update_status($status_old);
                $order->add_order_note(
                    sprintf(__('Customer points are insufficient. Order status back to %s', 'woocommerce-points-manager'), 
                        wc_get_order_status_name( $status_old ))
                );
                return false;
            }
            $points = $wc_points->sys->calculate_points_to_new_factor(
                $points_to_redeem,
                get_post_meta($order_id, '_conversion_factor', true),
                $customer->get_factor()
            );
            $customer->points->insert_transaction(
                $points, 'order-resume', $order_id, __('Redeemed points', 'woocommerce-points-manager')
            );
            update_post_meta($order_id, '_redeemed_points_reversal', false);
        }
    }
    
    public function cart_total_html($price) {
        global $wc_points;
        remove_filter('wc_price', [$this, 'formatting_html']);
        $total = $wc_points->sys->is_only_points() ? 0 : WC()->cart->get_total();
        add_filter('wc_price', [$this, 'formatting_html'], 10, 4);
        return $total;
    }
    
    public function formatting_html($return, $price, $args, $unformatted_price) {
        if (is_admin()) {return $return;}
        global $wc_points;
        $price = $unformatted_price;
        $points = $wc_points->number_format($price * $wc_points->sys->get_current_user()->get_factor());
        switch ($wc_points->sys->get_price_option()) {
            case 'only_prices':
                $price = $return;
                break;
            case 'only_points':
                $price = $points . apply_filters('wc_points_label', ' PTS');
                break;
            default:
                $price = $return . __(' or ', 'woocommerce-points-manager') . $points . apply_filters('wc_points_label', ' PTS');
                break;
        }
        
        return $price;
    }
    
    public function formatted_order_total($formatted_total, $order, $tax_display, $display_refunded) {
        global $wc_points;
        $points_to_redeem = 0;
        foreach ($order->get_items('fee') as $item_fee) {
            if ($item_fee->get_name() === __('Discount', 'woocommerce-points-manager')) {
                $points_to_redeem += $item_fee->get_total();
            }
        }
        $points_to_redeem *= get_post_meta($order->get_id(), '_conversion_factor', true);
        remove_filter('wc_price', [$this, 'formatting_html']);
        $price = wc_price( $wc_points->sys->is_only_points() ? 0 : $order->get_total(), array( 'currency' => $order->get_currency() ) );
        add_filter('wc_price', [$this, 'formatting_html'], 10, 4);
        $price .= ' - ' . $wc_points->number_format(abs($points_to_redeem)) . apply_filters('wc_points_label', ' PTS');
        return $price;
    }
    
    public function load_session() {
        if (isset($_POST['wc_points_to_cash']) && is_numeric($_POST['wc_points_to_cash'])) {
            $to_cash = doubleval(sanitize_text_field($_POST['wc_points_to_cash']));
            WC()->session->set('wc_points_to_cash', $to_cash);
        }
    }
    
}