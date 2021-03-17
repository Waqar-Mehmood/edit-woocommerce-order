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

    $id = $order->get_id();
    $user_id = $order->get_user_id();

    if( $user_id != get_current_user_id() ) {
        return false;
    }

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
 * Check cart session for edited order
 *
 * @return boolean true|false
 */
function ewo_check_cart_session_for_edited_order() {
	$edited = WC()->session->get('edit_order');

    if( empty( $edited ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Get Product id from cart item key
 *
 * @param int $cart_item_key
 *
 * @return int
 */
function ewo_get_product_id_from_cart_item( $cart_item_key ) {
	$product_id = '';
	foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
		if( $key == $cart_item_key ) {
			$product_id = $cart_item['product_id'];
		}
	}
	return $product_id;
}

/**
 * Check if the produc type is pre-ordered
 *
 * @param int $product_id
 *
 * @return boolean
 */
function ewo_check_pre_order_product( $product_id ) {
	if( get_post_meta( $product_id, '_ywpo_preorder', true ) === 'yes' ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Write Logs
 *
 * @param Mixed $log
 *
 * @return void
 */

if( function_exists( 'write_log' ) ) {
    function write_log( $log ) {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}