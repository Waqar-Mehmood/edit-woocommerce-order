<?php declare( strict_types = 1 );

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