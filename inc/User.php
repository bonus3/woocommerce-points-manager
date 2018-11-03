<?php

/**
 * WooPoints User.
 * 
 * @package WooPoints
 */

namespace WooPoints;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * User class
 */

class User {
    
    /**
     * 
     * @var \WP_User
     */
    public $wp;
    
    /**
     * 
     * @var Points
     */
    public $points;
    
    /**
     * Setup
     * 
     * @param \WP_User|int $user
     */
    public function __construct($user = 0) {
        if ($user instanceof \WP_User) {
            $user_id = $user->ID;
            $this->wp = $user;
        } else if ($user) {
            $user_id = $user;
            $this->wp = new \WP_User($user);
        } else {
            $this->wp = wp_get_current_user();
            $user_id = $this->wp->ID;
        }
        $this->points = new Points($user_id, $this->get_points_expiration());
    }
    
    /**
     * Get wp user roles
     * 
     * @return string[]
     */
    public function get_roles() {
        return $this->wp->roles;
    }
    
    /**
     * Get max days points expiration
     * 
     * @global WordPress $wc_points
     * @return int
     */
    public function get_points_expiration() {
        global $wc_points;
        $days = 0;
        $sys = $wc_points->sys->get_roles();
        foreach ($this->get_roles() as $role) {
            if (!isset($sys[$role])) continue;
            if ($sys[$role]['expiration'] > $days) {
                $days = $sys[$role]['expiration'];
            }
        }
        return apply_filters('wc_points_get_points_expiration', $days, $this);
    }
    
    /**
     * Get conversion factor user 
     * 
     * @global \WooPoints\WordPress $wc_points
     * @return float
     */
    public function get_factor() {
        global $wc_points;
        $factor = 1;
        $sys = $wc_points->sys->get_roles();
        $apply_factor = $wc_points->sys->get_type_apply_factor();
        foreach ($this->get_roles() as $role) {
            if (!isset($sys[$role])) {continue;}
            if (($sys[$role]['factor'] < $factor && $apply_factor === 'min') || 
                    ($sys[$role]['factor'] > $factor)) {
                $factor = $sys[$role]['factor'];
            }
        }
        return apply_filters('wc_points_get_conversion_factor', $factor, $this);
    }
    
}