<?php

/**
 * WooPoints Widget.
 * Widget to current user points
 * 
 * @package WooPoints\Widgets
 */

namespace WooPoints\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Current points widget class
 */

class Current_Points_Widget extends \WP_Widget {
    /**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'wc_points_current_user_points',
			'description' => __('Current user points', 'woocommerce-points-manager'),
		);
		parent::__construct( 'wc_points_current_user_points_widget', 'Current user points Widget', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
                echo "<div class='wc_points_current_user_points widget widget_wc_points_current_user_points'>";
                echo "<h3>" . __('Current user points', 'woocommerce-points-manager') . '</h3>';
                echo do_shortcode('[wc_points_user_points]');
                echo "</div>";
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		// outputs the options form on admin
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}
}