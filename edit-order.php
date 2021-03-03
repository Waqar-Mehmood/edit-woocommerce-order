<?php declare(strict_types = 1);

/**
 * Plugin Name: Edit WooCommerce Order
 * Description: Edit Order Functionality @ WooCommerce My Account Page
 * Version: 1.1
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

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) == false ) {
	function general_admin_notice(){
		global $pagenow;
		if ( $pagenow == 'options-general.php' ) {
			 echo '<div class="notice notice-warning is-dismissible">
				 <p>Edit WooCommerce Order is not enabled. It requires WooCommerce in order to work.</p>
			 </div>';
		}
	}
	add_action('admin_notices', 'general_admin_notice');
}

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

/**
 * Hooks
 */
register_activation_hook( __FILE__, 'ewo_activate_plugin' );
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'ewo_order_again_statuses' );
add_filter( 'woocommerce_my_account_my_orders_actions', 'ewo_add_edit_order_my_account_orders_actions', 50, 2 );
add_filter( 'woocommerce_valid_order_statuses_for_cancel', 'ewo_valid_order_statuses_for_cancel', 10, 2 );

add_action( 'init', 'ewo_action_woocommerce_ordered_again', 10, 1 );
add_action( 'woocommerce_order_details_after_order_table', 'ewo_add_edit_order_button_after_order_table' );
add_action( 'woocommerce_before_account_orders', 'ewo_add_popup_to_account_orders_page');
add_action( 'woocommerce_cart_loaded_from_session', 'ewo_detect_edit_order' );
add_action( 'woocommerce_before_cart', 'ewo_show_me_session' );
add_action( 'woocommerce_cart_calculate_fees', 'ewo_use_edit_order_total', 20, 1 );
add_action( 'woocommerce_checkout_update_order_meta', 'ewo_save_edit_order' );
add_action( 'woocommerce_account_dashboard', 'ewo_user_credit' );



function ewo_action_woocommerce_ordered_again() { 

    if( isset( $_GET['cancel_order'], $_GET['_wpnonce'] ) && 
        $_GET['cancel_order'] != '' &&
        is_user_logged_in() &&
        wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'woocommerce-order_again' ) 
    ) {

		// Cancel previous order
		$order = new WC_Order( $_GET['cancel_order'] );
		$user_id = $order->get_user_id();

		if( $user_id != get_current_user_id() ) {
			return;
		}

        $order->update_status('cancelled', 'Order cancelled after editing.' );
		wc_add_order_item_meta( $order->get_id(), 'edit_order_status', $order->get_status(), true );
		$edit_order_url = site_url( '/my-account/view-order/'.$order->get_id().'/?order_again='.$order->get_id().'&edit_order='.$order->get_id() );
		$edit_order_url = add_query_arg( '_wpnonce', wp_create_nonce( 'woocommerce-order_again' ), $edit_order_url );

		wp_redirect( $edit_order_url );
		exit;
	}
}; 

/**
 * 
 * TODO
 * I will add a condition for pre-order products, then pre-order products cannot be editable in the "Edit Order".
 * 
 */
add_filter( 'woocommerce_add_to_cart_validation', 'bbloomer_only_one_in_cart', 99, 2 );
   
function bbloomer_only_one_in_cart( $passed, $added_product_id ) {

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product_id = $cart_item['product_id'];
		if( $product_id == $added_product_id ) {
			if( get_post_meta( $product_id, '_ywpo_item_preorder' ) ) {
				// echo '<pre>';
				// print_r( $passed );
				// echo '<pre>';
				return $passed;
			}
		}
	}

	// $order = new WC_Order( $added_product_id );

	// Get and Loop Over Order Items
	// foreach ( $order->get_items() as $item_id => $item ) {

		// echo '<pre>';
		// print_r( $item->get_meta( '_ywpo_item_preorder', true ) );
		// echo '<pre>';

	// }
		
	// echo '<pre>';
	// print_r( $cart );
	// echo '<pre>';

	// echo '<pre>';
	// print_r( $added_product_id );
	// echo '<pre>';

   	return $passed;
}

/**
 * @desc Remove in all product type
 */
// function wc_remove_all_quantity_fields( $return, $product ) {
//     return true;
// }
// add_filter( 'woocommerce_is_sold_individually', 'wc_remove_all_quantity_fields', 10, 2 );


add_filter( 'woocommerce_quantity_input_args', 'ts_woocommerce_quantity_selected_number', 10, 2 );

function ts_woocommerce_quantity_selected_number( $args, $product ) {

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		// echo '<pre>';
		// print_r( $cart_item['_ywpo_item_preorder'] );
		// print_r( $cart_item );
		// echo '<pre>';

		$product_id = $cart_item['product_id'];
		if( $product_id == $product->get_id() ) {
			// print_r( get_post_meta( $product_id, '_ywpo_item_preorder' ) );
			if( get_post_meta( $product_id, '_ywpo_item_preorder' ) ) {
				
				// echo '<pre>';
				// print_r( get_post_meta( $product_id, '_ywpo_item_preorder' ) );
				// echo '<pre>';
				// return $passed;
			}
		}
	}

	

	// echo '<pre>';
	// print_r( $product->get_id() );
	// echo '</pre>';

	// echo '<pre>';
	// print_r( get_post_meta( $product->get_id(), '_ywpo_item_preorder' ) );
	// echo '</pre>';

	if( get_post_meta( $product->get_id(), '_ywpo_item_preorder' ) ) {
		$args['input_value'] = 3; // Start from this value (default = 1)
		$args['max_value'] = 3; // Maximum quantity (default = -1)
		$args['min_value'] = 3; // Minimum quantity (default = 0)
		$args['step'] = 3; // Increment or decrement by this value (default = 1)
	}

	// global $product;
	// if ( ! is_cart() ) {
	// 	if ( $product->get_slug() == "throw-pillow" ) {
	// 		$args['input_value'] = 3; // Start from this value (default = 1)
	// 		$args['max_value'] = 18; // Maximum quantity (default = -1)
	// 		$args['min_value'] = 3; // Minimum quantity (default = 0)
	// 		$args['step'] = 3; // Increment or decrement by this value (default = 1)
	// 	}
	// } else {
	// 	if ( $product->get_slug()=="throw-pillow" ) {
	// 		// Cart's 'min_value' is 0
	// 		$args['max_value'] = 18;
	// 		$args['step'] = 3;
	// 		$args['min_value'] = 3;
	// 	}
	// }

	return $args;
}


add_action( 'init', function() {

    // $order = new WC_Order( 122 );

    // echo '<pre>';
    // print_r( $order->get_id() );
    // echo '</pre>';

    // echo '<pre>';
    // print_r( $order->get_status() );
    // echo '</pre>';

} );