<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Check order
 * 
 * @param object $order
 * 
 * @return bool
 */
function ewo_check_order( $order ) {

    if( $order->get_payment_method() == 'bacs' && $order->has_status( 'on-hold' ) ) {
        return false;
    }

    $editorder_status = get_post_meta( $order->get_id(), '_edit_order', true );
    if( is_user_logged_in() && 
        $editorder_status == '' && ( 
            $order->has_status( 'processing' ) || 
            $order->has_status( 'pending' ) || 
            $order->has_status( 'on-hold' ) 
        ) 
    ) { 
        return true;
    }

}

/**
 * String Replace
 * 
 * @param string $content
 * @param object $order
 * 
 * @return string
 */
function ewo_replace_string( $content, $order ) {
    
    $credit = $order->get_total();
	if( $credit == 0 ) {
		$credit	= $order->get_subtotal();
    }
    
    $variables = [
        '{credit}' => wc_price( $credit ),
    ];

    foreach ($variables as $key => $value) {
        $content = str_replace( $key, $value, $content );
    }
    
    return $content;
}

/**
 * Write Logs
 * 
 * @param Mixed $log
 * 
 * @return void
 */

function write_log( $log ) {
    if ( true === WP_DEBUG ) {
        if ( is_array( $log ) || is_object( $log ) ) {
            error_log( print_r( $log, true ) );
        } else {
            error_log( $log );
        }
    }
}