<?php declare(strict_types = 1);

function ewo_activate_plugin() {
    if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
        wp_die( __( 'You must update WordPress to use this plugin', 'ewo' ) );
    }

    ewo_set_meta_field_for_orders();
}

function ewo_set_meta_field_for_orders() {
    
    // $query = new WC_Order_Query();
    // $query->set( 'limit', -1 );
    // $orders = $query->get_orders();

    // echo '<pre>';
    // print_r( $orders );
    // echo '</pre>';


    $args = array(
        'limit' => -1,
    );
    $orders = wc_get_orders( $args );

    // echo '<pre>';
    // print_r( $orders );
    // echo '</pre>';

    foreach ($orders as $key => $order) {

        
            

        $edit_order_disable = get_post_meta( $order->get_id(), 'edit_order_disable', true );
        
        if( empty( $edit_order_disable ) ) {
            update_post_meta( $order->get_id(), 'edit_order_disable', false );

            
        } else {
            // echo '<pre>';
            // print_r( $edit_order_disable );
            // echo '</pre>';
        }
        
        // echo '<pre>';
        // print_r( $edit_order_disable );
        // echo '</pre>';

    }

    

    // die();

    

}