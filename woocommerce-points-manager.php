<?php

/**
 * Plugin Name: WooCommerce Points Manager
 * Plugin URI: https://github.com/bonus3/woocommerce-points-manager
 * Description: WooCommerce Points Manager
 * Version: 1.0.2
 * Author: Anderson SG
 * Author URI: https://tec.andersonsg.com.br
 * Text Domain:  woocommerce-points-manager
 * 
 * @package WooPoints
*/

if (!defined('WC_POINTS_PATH')) {
    define('WC_POINTS_PATH', plugin_dir_path(__FILE__));
}

if (!defined('WC_POINTS_URI')) {
    define('WC_POINTS_URI', plugin_dir_url(__FILE__));
}

if (!defined('WC_POINTS_FOLDER')) {
    define('WC_POINTS_FOLDER', basename( dirname( __FILE__ )));
}

if (!defined('WC_POINTS_VERSION')) {
    define('WC_POINTS_VERSION', '1.0.0');
}

spl_autoload_register(function ($class) {
    if (strpos($class, "WooPoints") === false) {return;}
    $class = str_replace("\\", DIRECTORY_SEPARATOR, $class);
    $class = str_replace("WooPoints", "inc", $class);
    $path = WC_POINTS_PATH . $class . '.php';
    if (file_exists($path)) {
        include_once $path;
    }
});

/** @global \WooPoints\WordPress */
global $wc_points;

function wc_points_load() {
    global $wc_points;
    if (class_exists('WooCommerce')) {
        $wc_points = new WooPoints\WordPress();
    }
}
add_action('plugins_loaded', 'wc_points_load');

register_activation_hook(__FILE__, [\WooPoints\WordPress::class, 'create_tables']);