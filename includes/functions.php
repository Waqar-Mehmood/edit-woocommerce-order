<?php declare( strict_types = 1 );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once __DIR__ . '/helper.php';
require_once __DIR__ . '/popup.php';

/**
 *
 * 1. Allow Order Again for Processing Status
 *
 */
function ewo_order_again_statuses( $statuses ) {
    $statuses[] = 'processing';
    $statuses[] = 'pending';
    $statuses[] = 'on-hold';
    $statuses[] = 'cancelled';
    return $statuses;
}

/**
 *
 * 2. Add Order Actions @ My Account
 *
 * Actions
 * 1. Edit Order button
 *
 */
function ewo_add_edit_order_my_account_orders_actions( $actions, $order ) {

    if( ewo_check_order( $order ) == false ) {
        return $actions;
    }

    // ewo_add_popup_to_account_order_page( $order );

    $actions['edit-order'] = array(
        'url'  => '#edit_order_popup_' . $order->get_id(),
        'name' => __( 'Edit Order', 'editorder' )
    );

    return $actions;
}

/**
 *
 * 2. Add Order Actions @ Single Order Page & Thank You Page
 *
 * Actions
 * 1. Edit Order button
 *
 */
function ewo_add_edit_order_button_after_order_table( $order ) {

    if( ewo_check_order( $order ) == false ) {
        return;
    }

    //ewo_add_popup_to_account_order_page( $order );
    new Ewo_Create_Popup( $order );

    ?>

    <p class="edit-order-container">
        <a href="#edit_order_popup_<?= $order->get_id() ?>" class="button edit-order"><?= __( 'Edit Order', 'editorder' ) ?></a>
    </p>

    <?php
}

/**
 *
 * 3. Detect Edit Order Action and Store in Session
 *
 */
function ewo_detect_edit_order( $cart ) {

    if( isset( $_GET['edit_order'], $_GET['_wpnonce'] ) &&
        $_GET['edit_order'] != '' &&
        is_user_logged_in() &&
        wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'woocommerce-order_again' )
    ) {

        $order = new WC_Order( $_GET['edit_order'] );
        $previous_status = wc_get_order_item_meta( $order->get_id(),  'edit_order_status' );

        // Update Session
        if( $order->has_status( 'cancelled' ) ) {
            WC()->session->set( 'edit_order', absint( $_GET['edit_order'] ) );
        } else {
            WC()->session->__unset( 'edit_order' );
        }

        // Save User Credit
        // if( $order->has_status( 'processing' ) ) {
        if( $previous_status == 'processing' ) {
            if( $order->get_payment_method() == 'paypal' || $order->get_total() == 0 ) {

                $user_id = get_current_user_id();
                $credit  = $order->get_total();

                if( $credit == 0 ) {
                    $credit	= $order->get_subtotal();
                }

                if( metadata_exists( 'user', $user_id, 'credit' ) ) {
                    $previous_credit = get_user_meta( get_current_user_id(), 'credit', true );
                    update_user_meta( $user_id, 'credit',  $previous_credit + $credit );
                } else {
                    add_user_meta( $user_id, 'credit', $credit );
                }
            }
        }
    }
}

/**
 *
 * 4. Display Cart Notice: Edited Order
 *
 */
function ewo_show_me_session() {

    if( ! is_cart() ) return;
    $edited = WC()->session->get('edit_order');

    if( ! empty( $edited ) ) {
        $order  = new WC_Order( $edited );
        $credit = $order->get_total();

		if( $credit == 0 ) {
			$credit	= $order->get_subtotal();
		}

        if( $order->has_status( 'cancelled' ) ) {
            /**
             *
             * 1. Cash on delivery     (cod)
             * 2. Direct bank transfer (bacs)
             * 3. PayPal Standard      (paypal)
             * 4. Cheque payments      (cheque)
             *
             */
            if( $order->get_payment_method() == 'cod' ||
                $order->get_payment_method() == 'bacs' ||
                $order->get_payment_method() == 'cheque' ) {
                wc_print_notice( htmlspecialchars_decode( ewo_replace_string( esc_attr( get_option( 'ewo_popup_other_payment_methods' ) ), $order ) ), 'notice' );
            } else {
                wc_print_notice( htmlspecialchars_decode( ewo_replace_string( esc_attr( get_option( 'ewo_popup_paypal_payment_method' ) ), $order ) ), 'notice' );
            }
        }
    }
}

/**
 *
 * 5. Calculate New Total if Edited Order
 *
 */
function ewo_use_edit_order_total( $cart ) {

    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $edited = WC()->session->get('edit_order');

    if ( ! empty( $edited ) ) {
        $order = new WC_Order( $edited );

		if( ( $order->get_payment_method() == 'paypal' && $order->has_status( 'cancelled' ) ) || ( $order->get_total() == 0 && $order->has_status( 'cancelled' ) ) ) {

            $user_id = get_current_user_id();
            $credit  = '';
            if( metadata_exists( 'user', $user_id, 'credit' ) ) {
                $credit = -1 * get_user_meta( $user_id, 'credit', true );
            }

            WC()->session->set( 'cart_subtotal', WC()->cart->subtotal );

            $cart->add_fee( 'Credit', $credit );
        }
    } else {
		if( is_user_logged_in() ) {
			$user_id = get_current_user_id();
            $credit  = '';
            if( metadata_exists( 'user', $user_id, 'credit' ) ) {
                $credit = -1 * get_user_meta( $user_id, 'credit', true );
            }

			if( $credit > 0 ) {
				WC()->session->set( 'cart_subtotal', WC()->cart->subtotal );
	            $cart->add_fee( 'Credit', $credit );
			}
		}
	}
}

/**
 *
 * 6. Save Order Action if New Order is Placed
 *
 */
function ewo_save_edit_order( $order_id ) {
    $edited = WC()->session->get('edit_order');
    if ( ! empty( $edited ) ) {

        // Update this new order
        update_post_meta( $order_id, '_edit_order', $edited );
        $neworder      = new WC_Order( $order_id );
        $oldorder_edit = get_edit_post_link( $edited );
        $neworder->add_order_note( 'Order placed after editing. Old order number: <a href="' . $oldorder_edit . '">' . $edited . '</a>' );

        // Add note to previous order
        $oldorder      = new WC_Order( $edited );
        $neworder_edit = get_edit_post_link( $order_id );
        $oldorder->add_order_note( 'Order cancelled after editing. New order number: <a href="' . $neworder_edit . '">' . $order_id . '</a>' );
        WC()->session->set( 'edit_order', null );

        // Update user credit if order is completed with PayPal
		if( $oldorder->get_payment_method() == 'paypal' || $oldorder->get_total() == 0 ) {
			$user_id     = $neworder->get_user_id();
            $user_credit = get_user_meta( $user_id, 'credit', true );
            $new_credit  = 0;
            $neworder_subtotal = WC()->session->get( 'cart_subtotal');

            if($neworder->get_total() == 0) {

                $new_credit        = $user_credit - $neworder_subtotal;
            } else {
                if( $user_credit > $neworder_subtotal ) {
                    $new_credit = $user_credit - $neworder_subtotal;
                } else {
                    $new_credit = 0;
                }
            }

			update_user_meta( $user_id, 'credit',  $new_credit );
		}
	} else {
		$order          = new WC_Order( $order_id );
		$user_id        = $order->get_user_id();
		if( metadata_exists( 'user', $user_id, 'credit' ) ) {
			$user_credit    = get_user_meta( $user_id, 'credit', true );
			$new_credit     = 0;
			$order_subtotal = WC()->session->get( 'cart_subtotal');

			if($order->get_total() == 0) {
				$new_credit = $user_credit - $order_subtotal;
			} else {
				if( $user_credit > $order_subtotal ) {
					$new_credit = $user_credit - $order_subtotal;
				} else {
					$new_credit = 0;
				}
			}

			update_user_meta( $user_id, 'credit',  $new_credit );
		}
	}
}

/**
 * On Load
 * Check Order Again
 *
 * 1. Cancel previous order
 * 2. Redirect to Cart Page
 */
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
 * Disable the change quantity feature from cart item, if the order is edited order and product type is pre-ordered
 *
 * @param Mixed $sprintf
 * @param Mixed $sprintf
 * @param int $cart_item_key
 *
 * @return String|Int
 */
function ewo_change_quantity_input( $product_quantity, $cart_item_key, $cart_item ) {
    $product_id = $cart_item['product_id'];

	$edited = WC()->session->get('edit_order');

    if( empty( $edited ) ) {
		return $product_quantity;
	}

	if( get_post_meta( $product_id, '_ywpo_preorder', true ) === 'yes' ) {
		return '<span>' . $cart_item['quantity'] . '</span>';
	} else {
		return $product_quantity;
	}

}

/**
 * Remove cart item remove link, if the order is edited order and product type is pre-ordered
 *
 * @param Mixed $sprintf
 * @param int $cart_item_key
 *
 * @return Null|Mixed
 */
function ewo_filter_woocommerce_cart_item_remove_link( $sprintf, $cart_item_key ) {

	$product_id = ewo_get_product_id_from_cart_item( $cart_item_key );

	// Check if session is for edited order
	if( ewo_check_cart_session_for_edited_order() == false ) {
		return $sprintf;
	}

    if( ewo_check_pre_order_product( $product_id ) == false ) {
        return $sprintf;
    }
}