<?php

/**
 * WooPoints Points.
 * 
 * @package WooPoints
 */

namespace WooPoints;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Points class
 */

class Points {
    
    /**
     *
     * @var int 
     */
    private $user_id;
    
    /**
     *
     * @var int 
     */
    private $expiration;
    
    /**
     *
     * @var float 
     */
    private $current_points;
    
    /**
     * Setup
     * 
     * @param int $user_id
     * @param int $expiration
     */
    public function __construct($user_id, $expiration) {
        $this->user_id = $user_id;
        $this->expiration = $expiration;
        $this->update_current_points();
    }
    
    /**
     * Update current user points
     */
    public function update_current_points() {
        $this->current_points = $this->load_current_points();
    }
    
    /**
     * Get user points
     * 
     * @return float
     */
    public function get_current_points() {
        return apply_filters('wc_points_get_current_user_points', $this->current_points, $this);
    }
    
    /**
     * Query to calc current user points
     * 
     * @global \wpdb $wpdb
     * @return float
     */
    private function load_current_points() {
        global $wpdb;
        
        $result = $wpdb->get_results($wpdb->prepare("SELECT IFNULL(SUM(points), 0) AS points FROM " .
                $wpdb->prefix . "points_transaction WHERE user_id = %d", $this->user_id));
        
        return $result[0]->points;
    }
    
    /**
     * 
     * @global \wpdb $wpdb
     * @param float $points
     * @param string $codeword
     * @param int $order_id
     * @param string $description
     * @param int $reference
     * @return int Last insert id
     * @throws \Exception Is insert table error
     */
    public function insert_transaction($points, $codeword = '', $order_id = 0, $description = '', $reference = 0) {
        global $wpdb;
        if ($points == 0) {
            return false;
        }
        if (empty($codeword)) {
            $codeword = $points > 0 ? 'credit' : 'debit';
        }
        $data = [
            'user_id' => $this->user_id,
            'entry' => current_time('mysql'),
            'order_id' => intval($order_id),
            'points' => $points,
            'current_points' => $this->get_current_points() + $points,
            'codeword' => $codeword,
            'inserted_by' => get_current_user_id(),
            'description' => $description,
            'reference' => $reference
        ];
        if ($points > 0 && $this->expiration > 0) {
            $data['expired'] = date('Y-m-d H:i:s', strtotime('+' . $this->expiration . ' days'));
        }
        $insert = $wpdb->insert($wpdb->prefix . 'points_transaction', apply_filters('wc_points_transaction_data', $data, $this));
        if (!$insert) {
            throw new \Exception($wpdb->last_error);
        }
        $this->load_current_points();
        do_action('wc_points_after_transaction', $wpdb->insert_id, $data, $this);
        return $wpdb->insert_id;
    }
    
    /**
     * Get user extrcat with pagination
     * 
     * @global \wpdb $wpdb
     * @global WordPress $wc_points
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function extract($limit = 10, $page = 1) {
        global $wpdb, $wc_points;
        $offset = ($page - 1) * $limit;
        $total = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) AS total FROM {$wpdb->prefix}points_transaction WHERE user_id = %d", 
                $this->user_id)
        );
        $transactions = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}points_transaction WHERE user_id = %d "
            . "ORDER BY id DESC LIMIT %d, %d",
            $this->user_id, $offset, $limit)
        );
        //$format_date = get_option('date_format') . ' ' . get_option('time_format');
        $format_date = get_option('date_format');
        foreach ($transactions as $key => $transaction) {
            $transactions[$key]->entryFormated = date($format_date, strtotime($transaction->entry));
            $transactions[$key]->description = $transaction->description . ' '
                    . ($transaction->order_id ? __('- Order: ', 'woocommerce-points-manager') . $transaction->order_id : '');
            $transactions[$key]->pointsFormated = $wc_points->number_format($transaction->points);
            if ($transaction->expired !== '0000-00-00 00:00:00') {
                $transactions[$key]->expiredFormated = date($format_date, strtotime($transaction->expired));
            }
        }
        $data = [
            'total' => $total,
            'data' => $transactions
        ];
        return apply_filters('wc_points_extract', $data, $limit, $page, $this);
    }
    
    /**
     * Calculate and debit expired points
     * @global \wpdb $wpdb
     */
    public function expired_points() {
        global $wpdb;
        $last_credits = $wpdb->get_results(
            $wpdb->prepare("SELECT id, points "
                . "FROM {$wpdb->prefix}points_transaction "
                . "WHERE user_id = %d AND points > 0 AND reference = 0 AND expired < CURRENT_DATE() "
                . "ORDER BY entry DESC ", $this->user_id)
        );
        $current_points = $this->get_current_points();
        $debit = 0;
        foreach ($last_credits as $credit) {
            $debit_aux = ($current_points <= $credit->points ? $current_points : $credit->points);
            $debit += $debit_aux;
            $current_points -= $debit_aux;
            $wpdb->update($wpdb->prefix . 'points_transaction', ['reference' => 1], ['id' => $credit->id]);
        }
        if (apply_filters('wc_points_can_debit_points_expired', $debit > 0, $debit, $this, $last_credits)) {
            $this->insert_transaction($debit * -1, 'expired', 0, __('Expired points', 'woocommerce-points-manager'));
        }
    }
    
}