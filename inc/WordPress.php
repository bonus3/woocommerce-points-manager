<?php

/**
 * WooPoints WordPress
 * 
 * Enable admin init
 * 
 * @package WooPoints
 */

namespace WooPoints;
use WooPoints\Widgets\Current_Points_Widget;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * WordPress class
 */

class WordPress {
    
    /** @var WC_Points */
    public $sys;
    
    public function __construct() {
        add_filter('user_row_actions',             [$this, 'add_row_actions'], 10, 2);
        add_action('admin_footer',                 [$this, 'add_thickbox']);
        add_action('admin_enqueue_scripts',        [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts',           [$this, 'public_enqueue_scripts']);
        add_action('wp_ajax_get_user_data',        [$this, 'get_user_data']);
        add_action('wp_ajax_wc_points_operations', [$this, 'points_operation']);
        add_action('admin_menu',                   [$this, 'add_admin_menu']);
        add_action('widgets_init',                 [$this, 'load_widgets']);
        add_action('add_meta_boxes',               [$this, 'add_meta_box']);
        add_action('init',                         [$this, 'load_textdomain']);
        add_shortcode('wc_points_user_points',     [$this, 'shortcode_user_points']);
        add_shortcode('wc_points_user_extract',    [$this, 'shortcode_user_extract']);
        add_action('plugin_action_links_woocommerce-points-manager/woocommerce-points-manager.php', [$this, 'settings_link']);
        $this->load_wc_points();
    }
    
    /**
     * Load WC_Points class
     */
    public function load_wc_points() {
        $this->sys = new WC_Points();
    }
    
    /**
     * Add link action to manager user points
     * 
     * @param string[] $actions
     * @param \WP_User $user
     * @return string[]
     */
    public function add_row_actions($actions, \WP_User $user) {
        $actions['wc_points_user'] = 
            "<a href='#TB_inline?width=600&height=550&inlineId=wc-points-thickbox' class='wc-points-user thickbox' "
                . "data-user='" . $user->ID . "'>" .
                __('Points manager', 'woocommerce-points-manager') .
            "</a>";
        return $actions;
    }
    
    /**
     * Add thickbox to user admin page
     */
    public function add_thickbox() {
        add_thickbox();
        include_once WC_POINTS_PATH . 'views/user-points-manager.php';
    }
    
    /**
     * Load admin scripts and css
     * 
     * @param string $hook
     */
    public function admin_enqueue_scripts($hook) {
        if ($hook === 'users.php' || get_current_screen()->id === 'woocommerce_page_wc_points_menu') {
            $wp_scripts = wp_scripts();
            wp_enqueue_style('wc-points-styles',
                'http://ajax.googleapis.com/ajax/libs/jqueryui/' . $wp_scripts->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.css',
                false,
                WC_POINTS_VERSION,
                false);
            
            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script('jquery-ui-tabs');
            
            wp_enqueue_script('wc-points-admin', WC_POINTS_URI . 'assets/js/wc-points-admin.js', ['jquery']);
            $data = [
                'adminUrl' => admin_url( 'admin-ajax.php' )
            ];
            wp_localize_script( 'wc-points-admin', 'wc_points_admin', $data );
        }
    }
    
    /**
     * Load public scripts and css
     */
    public function public_enqueue_scripts() {
        wp_enqueue_script('wc-points-public', WC_POINTS_URI . 'assets/js/wc-points.js', ['jquery']);
        $total_cart = WC()->cart->get_cart_contents_total() + WC()->cart->get_shipping_total();
        $data = array(
            'userPoints' => $this->sys->get_current_user()->points->get_current_points(),
            'userFactor' => $this->sys->get_current_user()->get_factor(),
            'cartSubtotalWithShipping' => $total_cart,
            'minPointsToUse' => $this->sys->get_minimum_points(false),
            'minPointsToUseIsPercent' => $this->sys->is_percent(),
            'maxPointsToUse' => $this->sys->get_maximum_points(false),
            'maxPointsToUseIsPercent' => $this->sys->is_percent('maximum'),
            'cartDiscount' =>  WC()->session->get('wc_points_to_cash', $this->sys->calculate_max_points($total_cart)),
            'ajaxUrl' => admin_url( 'admin-ajax.php' )
        );
        wp_localize_script( 'wc-points-public', 'wc_points', $data );
    }
    
    /**
     * Create table in DB
     * 
     * @global \wpdb $wpdb
     */
    public static function create_tables() {
        global $wpdb;
        $sql = "CREATE TABLE IF NOT EXISTS `". $wpdb->prefix . "points_transaction` ("
                . "`id` INT NOT NULL AUTO_INCREMENT , "
                . "`user_id` INT NOT NULL , "
                . "`entry` DATETIME NOT NULL , "
                . "`expired` DATETIME NOT NULL , "
                . "`order_id` INT NOT NULL , "
                . "`description` VARCHAR(255) NOT NULL , "
                . "`points` DECIMAL(10,2) NOT NULL , "
                . "`current_points` DECIMAL(10,2) NOT NULL , "
                . "`codeword` VARCHAR(100) NOT NULL , "
                . "`inserted_by` INT NOT NULL , "
                . "`reference` INT NOT NULL , "
                . "PRIMARY KEY (`id`), "
                . "INDEX `user` (`user_id` ASC)"
            . ");";
        $wpdb->query($sql);
    }
    
    /**
     * Helper to response ajax
     * 
     * @param mixed $data
     * @param string $message
     * @param boolean $status
     */
    public static function response($data, $message, $status = true) {
        $json = [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
        echo json_encode($json);
        die();
    }
    
    /**
     * Ajax get user data
     */
    public function get_user_data() {
        $user = new User(intval(sanitize_text_field($_POST['user_id'])) );
        if (!current_user_can('manage_options') || wp_get_current_user()->ID !== $user->wp->ID) {
            $this->response(null, __('Not authorized', 'woocommerce-points-manager'), false);
        }
        $extract_limit = isset($_POST['extract_limit']) ? intval(sanitize_text_field($_POST['extract_limit'])) : 10;
        $extract_page = isset($_POST['extract_page']) ? intval(sanitize_text_field($_POST['extract_page'])) : 1;
        $data = [
            'id' => $user->wp->ID,
            'currentPoints' => $user->points->get_current_points(),
            'currentPointsFormated' => $this->number_format($user->points->get_current_points()),
            'extract' => $user->points->extract($extract_limit, $extract_page),
            'limit' => $extract_limit,
            'page' => $extract_page,
            'conversionFactor' => $this->number_format($user->get_factor())
        ];
        $this->response($data, __('User data', 'woocommerce-points-manager'));
    }
    
    /**
     * Add WC Points menu
     */
    public function add_admin_menu() {
        add_submenu_page('woocommerce', 'WC Points', 'WC Points', 'manage_options', 'wc_points_menu', function () {
            include_once WC_POINTS_PATH . 'views/user-points-config.php';
        });
    }
    
    /**
     * Users points management
     * 
     * @throws \Exception If fail insert transaction
     */
    public function points_operation() {
        $user_id = $_POST['user_id'];
        $points = wc_format_decimal(sanitize_text_field($_POST['balance_adjustment']));
        $description = isset($_POST['balance_adjustment_description']) ? sanitize_text_field($_POST['balance_adjustment_description']) : '';
        if (!($user_id > 0 && $points != 0)) {
            $this->response(null, __('Invalid data', 'woocommerce-points-manager'), false);
        }
        if (!current_user_can('manage_options')) {
            $this->response(null, __('Not authorized', 'woocommerce-points-manager'), false);
        }
        $user = new User($user_id);
        try {
            $user->points->insert_transaction($points, '', 0, $description);
            $this->response(null, __('Success', 'woocommerce-points-manager'));
        } catch (\Exception $e) {
            $this->response($e->getMessage(), __('An error has occurred', 'woocommerce-points-manager'), false);
        }
    }
    
    /**
     * Shortcode current user points
     * 
     * @param string[] $atts
     */
    public function shortcode_user_points($atts) {
        return $this->number_format($this->sys->get_current_user()->points->get_current_points())
                . apply_filters('wc_points_label', ' PTS');
    }
    
    /**
     * Helper number format by WooCommerce
     * 
     * @param int|float $number
     * @return string
     */
    public function number_format($number) {
        extract([
            'decimal_separator'  => wc_get_price_decimal_separator(),
            'thousand_separator' => wc_get_price_thousand_separator(),
            'decimals'           => wc_get_price_decimals()
        ]);
        return number_format( $number, $decimals, $decimal_separator, $thousand_separator );
    }
    
    /**
     * Load Widget
     */
    public function load_widgets() {
        register_widget(Current_Points_Widget::class);
    }
    
    /**
     * Add meta box to admin order, to manager order points
     */
    public function add_meta_box() {
        add_meta_box(
            'wc-points-order-meta-box',
            __('Order point management', 'woocommerce-points-manager'), 
            [$this, 'order_points_meta_box'],
            'shop_order',
            'side'
        );
    }
    
    /**
     * Load meta box content
     * 
     * @param \WP_Post $post wp_post
     */
    public function order_points_meta_box($post) {
        $order = wc_get_order($post->ID);
        $user = new User($order->get_customer_id());
        $redeemed_points = $this->number_format(get_post_meta($post->ID, '_redeemed_points', true));
        $conversion_factor = $this->number_format(get_post_meta($post->ID, '_conversion_factor', true));
        $current_points = $this->number_format($user->points->get_current_points());
        include_once WC_POINTS_PATH . 'views/meta-box-order-points.php';
    }
    
    /**
     * Add link to setting in plugin row
     * @param string[] $links
     * @return string[]
     */
    public function settings_link($links) {
        $links = array_merge( array(
            '<a href="' . esc_url( admin_url( '/admin.php?page=wc_points_menu' ) ) . '">' . __('Settings') . '</a>'
	), $links );
	return $links;
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'woocommerce-points-manager', false, WC_POINTS_FOLDER . '/languages' ); 
    }
    
    /**
     * 
     * @global \WP $wp
     * @param string[] $atts
     */
    public function shortcode_user_extract($atts) {
        global $wp;
        $offset = sanitize_text_field($_GET['offset']);
        $wcpp = sanitize_text_field($_GET['wcpp']);
        $limit = isset($offset) && $offset <= 100 ? intval($offset) : 5;
        $page = isset($wcpp) && $wcpp >= 1 ? intval($wcpp) : 1;
        $next_link = add_query_arg([
            'wcpp' => $page + 1
        ], home_url( $wp->request ));
        $previous_link = add_query_arg([
            'wcpp' => $page > 1 ? $page - 1 : $page
        ], home_url( $wp->request ));
        $extract = $this->sys->get_current_user()->points->extract($limit, $page)['data'];
        include_once WC_POINTS_PATH . 'views/user-extract.php';
    }
    
}