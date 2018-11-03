<?php

/**
 * WooPoints WC_Points. Enable points platform
 * 
 * Enable admin init
 * 
 * @package WooPoints
 */

namespace WooPoints;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Points class
 */

class WC_Points {
    
    /**
     * If points redemption is enabled
     * 
     * @var boolean
     */
    private $enabled;
    
    /**
     * If points redemption is only with points
     * 
     * @var type 
     */
    private $only_points;
    
    /**
     * Type of products price show
     * @var string 
     */
    private $price_option;
    
    /**
     * Minimal points or percent to order redemption
     * 
     * @var float 
     */
    private $minimum_points;
    
    /**
     * Maximum points or percent to order redemption
     * 
     * @var float 
     */
    private $maximum_points;
    
    /**
     * If minimal points is percent
     * 
     * @var boolean
     */
    private $minimum_points_percent;
    
    /**
     * If maximum points or percent to order redemption
     * 
     * @var boolean 
     */
    private $maximum_points_percent;
    
    /**
     * If user has more than one profile with different factors, get min or max factor
     * 
     * @var string 
     */
    private $apply_factor;
    
    /**
     * WP roles points options
     * 
     * @var array
     */
    private $roles_factor = [];
    
    /**
     * User object
     * 
     * @var User
     */
    private $current_user;
    
    /**
     * WooCommerce object
     * 
     * @var WooCommerce
     */
    public $woo;
    
    /**
     * Setup class
     */
    public function __construct() {
        $this->enabled          = get_option('wc_points_enabled', true);
        $this->only_points      = get_option('wc_points_only_points', false);
        $this->price_option     = get_option('wc_points_price_option', 'prices_points');
        $this->minimum_points   = $this->percent_or_decimal(get_option('wc_points_minimum_points', 0));
        $this->maximum_points   = $this->percent_or_decimal(get_option('wc_points_maximum_points', 0), 'maximum');
        $this->apply_factor     = get_option('wc_points_apply_factor', 'max');
        add_action('init', [$this, 'loading_current_user']);
        add_action('admin_post_wc_points_settings', [$this, 'save_data']);
        
        $this->load_roles_factor();
        if ($this->enabled) {
            $this->woo = new WooCommerce();
        }
    }
    
    /**
     * Return if points redemption is enabled
     * 
     * @return boolean
     */
    public function is_enabled() {
        return apply_filters('wc_points_is_enabled', (bool)$this->enabled);
    }
    
    /**
     * Return if points redemption is only with points 
     * 
     * return boolean
     */
    public function is_only_points() {
        return apply_filters('wc_points_is_only_points', (bool)$this->only_points);
    }
    
    /**
     * Return WooCoomerce price option
     * 
     * @return string only_prices|only_points|prices_points
     */
    public function get_price_option() {
        return apply_filters('wc_points_price_option', $this->price_option);
    }
    
    /**
     * Get minimum points value to redemption
     * 
     * @param boolean $set_percent
     * @return string
     */
    public function get_minimum_points($set_percent = true) {
        return apply_filters('wc_points_minimum_points', $this->minimum_points . ($this->is_percent() && $set_percent ? '%' : ''));
    }
    
    /**
     * Get maximum points value to redemption
     * 
     * @param boolean $set_percent
     * @return string
     */
    public function get_maximum_points($set_percent = true) {
        return apply_filters('wc_points_maximum_points', $this->maximum_points . ($this->is_percent('maximum') && $set_percent ? '%' : ''));
    }
    
    /**
     * Get if minimum or maximum is percent
     * 
     * @param string $type
     * @return boolean
     */
    public function is_percent($type = 'minimum') {
        return $type === 'minimum' ? $this->minimum_points_percent : $this->maximum_points_percent;
    }
    
    /**
     * Get apply factor type
     * If user has more than one profile with different factors, get min or max factor
     * 
     * @return string
     */
    public function get_type_apply_factor() {
        return apply_filters('wc_points_apply_factor', $this->apply_factor);
    }
    
    /**
     * Get wp roles config 
     * 
     * @return array
     */
    public function get_roles() {
        return apply_filters('wc_points_roles', $this->roles_factor);
    }
    
    /**
     * load wp roles factor
     */
    private function load_roles_factor() {
        foreach (wp_roles()->roles as $role => $data) {
            $this->roles_factor[$role] = [
                'name' => $data['name'],
                'factor' => get_option('wc_points_role_' . $role, 1),
                'expiration' => get_option('wc_points_expiration_' . $role, 0)
            ];
        }
    }
    
    /**
     * Save config
     */
    public function save_data() {
        $data = apply_filters('wc_points_config_data', [
            'enabled'           => isset($_POST['enable_points_manager']) && !empty($_POST['enable_points_manager']),
            'only_points'       => isset($_POST['redemption_only_points']) && !empty($_POST['redemption_only_points']),
            'price_option'      => in_array($_POST['price_or_points'], ['only_prices', 'only_points', 'prices_points']) ? $_POST['price_or_points'] : 'prices_points',
            'minimum_points'    => $this->percent_or_decimal($_POST['minimum_points']),
            'maximum_points'    => $this->percent_or_decimal($_POST['maximum_points'], 'maximum'),
            'apply_factor'      => in_array($_POST['apply_factor'], ['min', 'max']) ? $_POST['apply_factor'] : 'max'
        ]);
        $this->enabled          = $data['enabled'];
        $this->only_points      = $data['only_points'];
        $this->price_option     = $data['price_option'];
        $this->minimum_points   = $data['minimum_points'];
        $this->maximum_points   = $data['maximum_points'];
        $this->apply_factor     = $data['apply_factor'];
        $this->set_data_roles();
        $this->save();
    }
    
    /**
     * Check if value is in float or percent
     * @param string $value
     * @param string $type
     * @return float
     */
    public function percent_or_decimal($value, $type = 'minimum') {
        $percent = false;
        if (substr($value, -1) === '%') {
            $value = substr($value, 0, -1);
            $percent = true;
        }
        if ($type === 'minimum') {
            $this->minimum_points_percent = $percent;
        } else {
            $this->maximum_points_percent = $percent;
        }
        
        return \wc_format_decimal($value, 2, true);
    }
    
    /**
     * Set POST values to user roles
     */
    private function set_data_roles() {
        foreach (wp_roles()->roles as $role => $data) {
            $this->roles_factor[$role]['factor'] = wc_format_decimal($_POST['wc_points_role_' . $role], 2, true);
            $this->roles_factor[$role]['expiration'] = $_POST['wc_points_expiration_' . $role];
        }
    }
    
    /**
     * Save data in wp options
     */
    private function save() {
        update_option('wc_points_enabled', intval($this->enabled), true);
        update_option('wc_points_only_points', intval($this->only_points), true);
        update_option('wc_points_price_option', $this->price_option, true);
        update_option('wc_points_minimum_points', $this->minimum_points . ($this->minimum_points_percent ? '%' : ''), true);
        update_option('wc_points_maximum_points', $this->maximum_points . ($this->maximum_points_percent ? '%' : ''), true);
        update_option('wc_points_apply_factor', $this->apply_factor, true);
        foreach (wp_roles()->roles as $role => $data) {
            $factor = floatval($this->roles_factor[$role]['factor']);
            $expiration = intval($this->roles_factor[$role]['expiration']);
            update_option('wc_points_role_' . $role, $factor ? $factor : 1, true);
            update_option('wc_points_expiration_' . $role, $expiration > 0 ? $expiration : 0, true);
        }
        wp_redirect(admin_url('admin.php?page=wc_points_menu'));
        die();
    }
    
    /**
     * Load current user
     */
    public function loading_current_user() {
        $this->current_user = new User();
        $this->expired_points();
    }
    
    /**
     * Get current user
     */
    public function get_current_user() {
        return $this->current_user;
    }
    
    /**
     * Calculate minimun order points value
     * 
     * @param float $total
     * @return float
     */
    public function calculate_min_points($total) {
        $minimum = $this->get_minimum_points(false);
        if ($this->is_percent()) {
            $min = $total * ($minimum / 100);
        } else {
            $min = round($minimum / $this->get_current_user()->get_factor(), 2);
        }
        return $min;
    }
    
    /**
     * Get maximum order ponts value
     * 
     * @param float $total
     * @return float
     */
    public function calculate_max_points($total) {
        $maximum = $this->get_maximum_points(false);
        if ($this->is_percent('maximum')) {
            $max = $total * ($maximum / 100);
        } else {
            $max = round($maximum  / $this->get_current_user()->get_factor(), 2);
        }
        return $max;
    }
    
    /**
     * Calculate points of new conversiona factor
     * 
     * @param float $points
     * @param float $current_factor
     * @param float $new_factor
     * @return float
     */
    public function calculate_points_to_new_factor($points, $current_factor, $new_factor) {
        return $points * $new_factor / $current_factor;
    }
    
    /**
     * Call expired points procedure
     */
    public function expired_points() {
        if (!is_user_logged_in()) {return;}
        $user = $this->current_user->wp;
        $now = date('Y-m-d');
        if (get_user_meta($user->ID, '_check_expired_points', true) === $now) {return;}
        $this->current_user->points->expired_points();
        update_user_meta($user->ID, '_check_expired_points', $now);
    }
    
}