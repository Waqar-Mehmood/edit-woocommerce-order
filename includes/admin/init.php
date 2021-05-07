<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once __DIR__ . '/notices.php';
require_once __DIR__ . '/columns.php';
require_once __DIR__ . '/options-page.php';

function ewo_admin_init() {
    add_action( 'admin_notices', 'general_admin_notice' );

    add_action( 'manage_shop_order_posts_custom_column', 'ewo_manage_order_columns', 10, 2 );
    add_filter( 'manage_edit-shop_order_columns', 'ewo_add_new_locked_order_columns' );
    add_filter( "manage_edit-shop_order_sortable_columns", 'ewo_sort_order_function' );

    add_action( 'pre_get_posts', 'ewo_orders_filter_by_order_locked_column' );

    add_action( 'wp_ajax_productmetasave', 'ewo_process_ajax' );
    add_action( 'admin_footer', 'ewo_jquery_event' );

}