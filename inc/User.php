<?php

namespace WooPoints;

class User {
    
    /** @var \WP_User */
    public $wp;
    /** @var Points */
    public $points;
    
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
    
    public function get_roles() {
        return $this->wp->roles;
    }
    
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
    
    public function get_factor() {
        global $wc_points;
        $factor = 1;
        $sys = $wc_points->sys->get_roles();
        foreach ($this->get_roles() as $role) {
            if (!isset($sys[$role])) continue;
            if ($sys[$role]['factor'] > $factor) {
                $factor = $sys[$role]['factor'];
            }
        }
        return apply_filters('wc_points_get_conversion_factor', $factor, $this);
    }
    
}