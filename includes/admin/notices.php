<?php declare( strict_types = 1 );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

function general_admin_notice() {
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) == false ) {
        global $pagenow;
        if ( $pagenow == 'options-general.php' ) {
            echo '<div class="notice notice-warning is-dismissible">
                <p>'. __( 'Edit WooCommerce Order is not enabled. It requires WooCommerce in order to work.', 'ewo' ) .'</p>
            </div>';
        }
    }
}