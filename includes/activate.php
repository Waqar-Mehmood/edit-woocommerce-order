<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

function ewo_activate_plugin() {
    if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
        wp_die( __( 'You must update WordPress to use this plugin', 'editorder' ) );
    }

    ewo_set_meta_field_for_orders();
}

function ewo_set_meta_field_for_orders() {

    $args = array(
        'limit' => 10,
    );
    $orders = wc_get_orders( $args );

    foreach ($orders as $key => $order) {
        $edit_order_disable = get_post_meta( $order->get_id(), 'edit_order_disable', true );

        if( empty( $edit_order_disable ) ) {
            update_post_meta( $order->get_id(), 'edit_order_disable', false );
        }
    }
}