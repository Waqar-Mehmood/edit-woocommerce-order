<?php declare( strict_types = 1 );

/**
 * Plugin Name: Edit WooCommerce Order
 * Description: Edit Order Functionality @ WooCommerce My Account Page
 * Version: 1.2
 * Author: Waqar Mehmood
 * Author URI: https://iamwaqar.com
 * Text Domain: editorder
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Setup
 */
$plugin_data = get_file_data( __FILE__, array('Version' => 'Version'), false );
$plugin_version = $plugin_data['Version'];

define ( 'EWO_CURRENT_VERSION', $plugin_version );
define ( 'EWO_PLUGIN_PATH', plugin_dir_url( __FILE__ ) );

/**
 * Includes
 */
include( 'includes/activate.php' );
include( 'includes/enqueue.php' );
include( 'includes/functions.php' );
include( 'includes/cancel-order.php' );
include( 'includes/user-credit.php' );

// Admin
include( 'admin/create-column-in-shop-order.php' );
include( 'admin/change-order-status.php' );
include( 'admin/notices.php' );

/**
 * Hooks
 */
register_activation_hook( __FILE__, 'ewo_activate_plugin' );
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'ewo_order_again_statuses' );
add_filter( 'woocommerce_my_account_my_orders_actions', 'ewo_add_edit_order_my_account_orders_actions', 50, 2 );
add_filter( 'woocommerce_valid_order_statuses_for_cancel', 'ewo_valid_order_statuses_for_cancel', 10, 2 );
add_filter( 'woocommerce_cart_item_quantity', 'ewo_change_quantity_input', 10, 3);
add_filter( 'woocommerce_cart_item_remove_link', 'ewo_filter_woocommerce_cart_item_remove_link', 100, 2 );

// Admin
add_action( 'admin_notices', 'general_admin_notice' );

// Front End
add_action( 'init', 'ewo_action_woocommerce_ordered_again', 10, 1 );
add_action( 'woocommerce_order_details_after_order_table', 'ewo_add_edit_order_button_after_order_table' );
add_action( 'woocommerce_before_account_orders', 'ewo_add_popup_to_account_orders_page');
add_action( 'woocommerce_cart_loaded_from_session', 'ewo_detect_edit_order' );
add_action( 'woocommerce_before_cart', 'ewo_show_me_session' );
add_action( 'woocommerce_cart_calculate_fees', 'ewo_use_edit_order_total', 20, 1 );
add_action( 'woocommerce_checkout_update_order_meta', 'ewo_save_edit_order' );
add_action( 'woocommerce_account_dashboard', 'ewo_user_credit' );