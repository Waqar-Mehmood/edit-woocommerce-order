<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once __DIR__ . '/helper.php';

function ewo_add_popup_to_account_orders_page( $has_orders ) {
    ## ==> Define HERE the statuses of that orders
    $order_statuses = array('wc-on-hold', 'wc-processing', 'wc-pending');

    ## ==> Define HERE the customer ID
    $customer_user_id = get_current_user_id(); // current user ID here for example

    // Getting current customer orders
    $customer_orders = wc_get_orders( array(
        'meta_key' => '_customer_user',
        'meta_value' => $customer_user_id,
        'post_status' => $order_statuses,
        'numberposts' => -1
    ) );

    // Loop through each customer WC_Order objects
    if ( $customer_orders ) {
        $obj_create_popup = new Ewo_Create_Popup( null, false );
        foreach($customer_orders as $order ) {
            $obj_create_popup->set_order( $order );
            $obj_create_popup->create_popup();
        }
    }
}

class Ewo_Create_Popup {

    private $order;
    private $get_edit_order_disable;

    /**
	 * This is our constructor
     *
     * @param Object $order
     * @param Boolean $show
	 *
	 * @return void
	 */
    public function __construct( $order = null, $show = true ) {

        if( $order ) {
            $this->set_order( $order );
        }

        if( $show ) {
            $this->create_popup();
        };

	}

    /**
	 * Set Order object
     *
     * @param Object $order
	 *
	 * @return void
	 */
    public function set_order( $order ) {
        $this->order = $order;
        $this->get_edit_order_disable = get_post_meta( $this->order->get_id(), 'edit_order_disable', true );
        $this->get_edit_order_disable = empty( $this->get_edit_order_disable ) ? 'no' : $this->get_edit_order_disable;
    }

    public function create_popup() {
        $order = $this->order;
		$id = 'edit_order_popup_' . $order->get_id();
        ?>

        <div class="ewo-popup">
            <div id="<?= $id ?>" class="ewo-modal">
                <div class="ewo-modal-content">
                    <span onclick="document.getElementById('<?= $id ?>').style.display='none'" class="ewo-close" title="Close Modal">×</span>
                    <div class="ewo-container">
                        <header>
                            <h1><?php _e( 'NOTE', 'editorder' ) ?></h1>
                        </header>
                        <div class="ewo-popup-body"><?php $this->get_popup_message(); ?></div>
                        <footer><?php $this->get_popup_actions(); ?></footer>
                    </div>
                </div>
            </div>
        </div>

        <?php
	}

    private function get_popup_message() {

        if( $this->get_edit_order_disable == 'no' ) {
            if( $this->order->get_payment_method() == 'cod' ||
                $this->order->get_payment_method() == 'bacs' ||
                $this->order->get_payment_method() == 'cheque' ) {

                echo htmlspecialchars_decode( ewo_replace_string( esc_attr( get_option( 'ewo_popup_other_payment_methods' ) ), $this->order ) );
            } else {
                echo htmlspecialchars_decode( ewo_replace_string( esc_attr( get_option( 'ewo_popup_paypal_payment_method' ) ), $this->order ) );
            }
        } else {
            echo htmlspecialchars_decode( ewo_replace_string( esc_attr( get_option( 'ewo_popup_order_locked' ) ), $this->order ) );
        }
    }

    private function get_popup_actions() {

        $order = $this->order;
        $id = 'edit_order_popup_' . $order->get_id();

        $edit_order_url = site_url() . wp_nonce_url(
            add_query_arg(
                [
                    'cancel_order' => $order->get_id()
                ]
            ),
            'woocommerce-order_again'
        );

        ?>

        <div class="ewo-clearfix">
            <a
                type="button"
                style="text-decoration: none;"
                onclick="document.getElementById('<?= $id ?>').style.display='none'"
                class="ewo-cancelbtn">Cancel</a>

            <?php
            if( $this->get_edit_order_disable == 'no' ) { ?>
                <a
                    href="<?= $edit_order_url; ?>"
                    style="text-decoration: none;"
                    type="button"
                    role="button"
                    onclick="document.getElementById('<?= $id ?>').style.display='none'"
                    class="ewo-deletebtn">
                    <?php _e( 'Confirm', 'editorder' ); ?>
                </a>
                <?php
            } ?>
        </div>

        <?php
    }
}