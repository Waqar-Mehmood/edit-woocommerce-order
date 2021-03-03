<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once __DIR__ . '/../includes/helper.php';

/**
 * 
 * Admin side add menu
 * 
 */
add_action( 'admin_menu', 'ewo_options_page' );
function ewo_options_page() {

    add_submenu_page(
        'woocommerce',
        'Change Woocommerce Order Status',
        'Options',
        'manage_options',
        'cwos',
        'ewo_options_page_html',   
        999
    );

    //call register settings function
	add_action( 'admin_init', 'ewo_register_settings' );
}

function ewo_register_settings() {
	//register our settings
	register_setting( 'ewo-plugin-settings-group', 'ewo_enable_change_order_status' );
	register_setting( 'ewo-plugin-settings-group', 'ewo_new_order_status' );
    register_setting( 'ewo-plugin-settings-group', 'ewo_locked_time' );
    
    register_setting( 'ewo-plugin-settings-group', 'ewo_mail_to' );
    register_setting( 'ewo-plugin-settings-group', 'ewo_mail_subject' );
    register_setting( 'ewo-plugin-settings-group', 'ewo_mail_message' );

    register_setting( 'ewo-plugin-popup-group', 'ewo_popup_order_locked' );
    register_setting( 'ewo-plugin-popup-group', 'ewo_popup_paypal_payment_method' );
    register_setting( 'ewo-plugin-popup-group', 'ewo_popup_other_payment_methods' );
}

function ewo_options_page_html() {

    // check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $admin_email = get_option( 'admin_email' );

    ?>

    <style>
        .ewo-form {
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0px 0px 16px rgb(0 0 0 / 30%);
        }

        .ewo-form hr  {
            margin: 0 -20px;
        }

        .ewo-variable:hover  {
            cursor: pointer;
        }
    </style>

    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form class="ewo-form" action="options.php" method="post">
            <?php
            // output security fields for the registered setting "ewo_options"
            settings_fields( 'ewo-plugin-settings-group' );
            // output setting sections and their fields
            // (sections are registered for "cwos", each field is registered to a specific section)
            do_settings_sections( 'ewo-plugin-settings-group' ); ?>

            <h2><?php _e( 'Order Status Settings', 'textdomain' ) ?></h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Enable Change Order Status', 'textdomain' ) ?></th>
                    <td>
                        <input 
                            type="checkbox" 
                            name="ewo_enable_change_order_status" 
                            <?php echo esc_attr( get_option('ewo_enable_change_order_status') ) == 'on' ? 'checked' : ''; ?> />
                        <label for="ewo_enable_change_order_status"><?php _e( 'Enable change order status before delivery date.', 'textdomain' ) ?></label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e( 'New Order Status', 'textdomain' ) ?></th>
                    <td>
                        <select name="ewo_new_order_status" id="">
                            <?php
                            $all_status = wc_get_order_statuses();
                            foreach ($all_status as $key => $value) { ?>
                                
                                <option 
                                    <?php echo esc_attr( get_option('ewo_new_order_status') ) == $key ? 'selected' : ''; ?>
                                    value="<?= $key; ?>">    
                                    <?= $value; ?>
                                </option>

                                <?php
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?= __( 'Time', 'textdomain' ) ?></th>
                    <td style="padding-bottom: 0">
                        <input 
                            type="number" 
                            step="1"
                            minimum="1"
                            maximum="100"
                            name="ewo_locked_time" 
                            placeholder="Hours"
                            value="<?php echo esc_attr( get_option('ewo_locked_time') ); ?>" />
                        <label for="ewo_locked_time"><?php _e( 'Hours', 'textdomain' ) ?></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th style="padding: 0" scope="row"></th>
                    <td style="padding-top: 0"><b><?php _e( 'Note:', 'textdomain' ) ?></b> <?php _e( 'Order status will be change according to that time before delivery time.', 'textdomain' ) ?></td>
                </tr>
            </table>

            <hr>

            <h2><?php _e( 'Form Settings', 'textdomain' ) ?></h2>

            <div class="variables">
                <h4><?php _e( 'Variables', 'textdomain' ) ?></h4>
                <p>
                    <span class="ewo-variable" onClick="ewo_copy_variable('{status_from}')">{status_from}</span>
                    <span class="ewo-variable" onClick="ewo_copy_variable('{status_to}')">{status_to}</span>
                </p>
                <p><b><?php _e( 'Note:', 'textdomain' ) ?></b> <?php _e( 'You can use variables in the textarea', 'textdomain' ) ?></p>
            </div>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'To', 'textdomain' ) ?></th>
                    <td>
                        <input 
                            style="width: 50%"
                            type="text" 
                            name="ewo_mail_to" 
                            placeholder="<?= $admin_email; ?>"
                            value="<?= esc_attr( get_option('ewo_mail_to') ); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e( 'Subject', 'textdomain' ) ?></th>
                    <td>
                        <input 
                            style="width: 50%"
                            type="text"
                            name="ewo_mail_subject"
                            value="<?= esc_attr( get_option('ewo_mail_subject') ); ?>" />
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?= __( 'Message', 'textdomain' ) ?></th>
                    <td>
                        <textarea 
                            style="width: 50%; min-height: 100px"
                            rows="5" 
                            cols="50"
                            name="ewo_mail_message" ><?php echo esc_attr( get_option('ewo_mail_message') ); ?></textarea>
                    </td>
                </tr>

            </table>
            
            <?php

            // output save settings button
            submit_button( __( 'Save Settings', 'textdomain' ), 'primary', 'submit', false ); ?>
        </form>
        
        <!-- Popup message form -->
        <form class="ewo-form" action="options.php" method="post">
            <?php
            // output security fields for the registered setting "ewo_options"
            settings_fields( 'ewo-plugin-popup-group' );
            // output setting sections and their fields
            // (sections are registered for "cwos", each field is registered to a specific section)
            do_settings_sections( 'ewo-plugin-popup-group' ); ?>

            <h2><?php _e( 'Popups', 'textdomain' ) ?></h2>

            <div class="variables">
                <h4><?php _e( 'Variables', 'textdomain' ) ?></h4>
                <p>
                    <span class="ewo-variable" onClick="ewo_copy_variable('{status_from}')">{credit}</span>
                </p>
                <p><b><?php _e( 'Note:', 'textdomain' ) ?></b> <?php _e( 'You can use variables in the textarea', 'textdomain' ) ?></p>
            </div>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Order Locked', 'textdomain' ) ?></th>
                    <td>
                        <textarea 
                            style="width: 50%; min-height: 100px"
                            rows="5" 
                            cols="50"
                            name="ewo_popup_order_locked" ><?php echo esc_attr( get_option('ewo_popup_order_locked') ); ?></textarea>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e( 'Paypal Payment Method', 'textdomain' ) ?></th>
                    <td>
                        <textarea 
                            style="width: 50%; min-height: 100px"
                            rows="5" 
                            cols="50"
                            name="ewo_popup_paypal_payment_method" ><?php echo esc_attr( get_option('ewo_popup_paypal_payment_method') ); ?></textarea>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php _e( 'Other Payment Methods', 'textdomain' ) ?></th>
                    <td>
                        <textarea 
                            style="width: 50%; min-height: 100px"
                            rows="5" 
                            cols="50"
                            name="ewo_popup_other_payment_methods" ><?php echo esc_attr( get_option('ewo_popup_other_payment_methods') ); ?></textarea>
                    </td>
                </tr>

            </table>
            
            <?php

            // output save settings button
            submit_button( __( 'Save Settings', 'textdomain' ), 'primary', 'submit', false ); ?>
        </form>
    </div>

    <script>
        function ewo_copy_variable( variable ) {
            var textArea = document.createElement("textarea");
            textArea.value = variable;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("Copy");
            textArea.remove();
        }
    </script>
    
    <?php
}

/**
 * 
 * WooCommerce: Check orders Every 3 Hours (Cron Job)
 * 
 */

/**
 * 1. Define a cron job interval if it doesn't exist
 */ 
add_filter( 'cron_schedules', 'ewo_check_every_3_hours' );
 
function ewo_check_every_3_hours( $schedules ) {
    $schedules['every_three_hours'] = array(
        // 'interval' => 60 * 60 * 3, // Last digit represents hours
        'interval' => 60, // Last digit represents hours
        'display'  => __( 'Every 3 hours' ),
    );
    return $schedules;
}

/**
 * 2. Schedule an event unless already scheduled
 */
add_action( 'wp', 'ewo_custom_cron_job' );
 
function ewo_custom_cron_job() {
   if ( ! wp_next_scheduled( 'ewo_woocommerce_change_order_status' ) ) {
      wp_schedule_event( time() + ( 60*60*8 ), 'every_three_hours', 'ewo_woocommerce_change_order_status' );
   }
}

/**
 * 3. Trigger "Change Order Status" when hook runs
 */
add_action( 'ewo_woocommerce_change_order_status', 'ewo_change_order_status' );
 
/**
 * 4. Check order and change status tp locked-order
 */
function ewo_change_order_status() {
    
    $date_one = time() + ( 60*60*8 ) + ( 60*60*24 );
	$date_two = time() + ( 60*60*8 ) + ( 60*60*20 );
	$orders = ewo_get_orders_before_after( $date_one, $date_two );
	
	if ( $orders ) {
		foreach ( $orders as $key => $order_id ) {
			$order = new WC_Order( $order_id );

			if ( !empty( $order ) ) {
                $id = $order->get_id();
                // $status_old = $order->get_status();
                update_post_meta( $id, 'edit_order_disable', 'yes' );

                // $order->update_status( 'locked-order' );
                // $status_new = $order->get_status();
                // ewo_send_mail( $id, $status_old, $status_new, $order );
			}
		}
   	}
}

/**
 * 5. Query WooCommerce database for completed orders between two timestamps
 */
function ewo_get_orders_before_after( $date_one, $date_two ) {
    global $wpdb;
    $p = $wpdb->prefix;
    $qry = "SELECT {$p}posts.ID
        FROM {$p}posts 
        INNER JOIN {$p}postmeta 
        ON ( {$p}posts.ID = {$p}postmeta.post_id ) 
        WHERE ( {$p}postmeta.meta_key = '_orddd_timeslot_timestamp' 
        AND {$p}postmeta.meta_value <= $date_one
        AND {$p}postmeta.meta_value >= $date_two )
        AND {$p}posts.post_type = 'shop_order'
        AND ( {$p}posts.post_status = 'wc-processing' 
        OR {$p}posts.post_status = 'wc-on-hold' 
        OR {$p}posts.post_status = 'wc-pending' )";
	
    $orders = $wpdb->get_results( $qry );
    write_log( $qry );
   	return $orders;
}
 
/**
 * 6. Send notification to admin
 * 
 * @param 
 */
function ewo_send_mail( $id, $status_transition_from, $status_transition_to, $order ) { 
    
    // write_log( '==============' );
    
    // $admin_email = get_option( 'admin_email' );

    // write_log( $admin_email );

    // write_log( $this_get_id );
	// write_log( $this_status_transition_from );
	// write_log( $this_status_transition_to );
    // // write_log( $instance );

    // write_log( '==============' );

    esc_attr( get_option('ewo_enable_change_order_status') );
    esc_attr( get_option('ewo_enable_change_order_status') );
    esc_attr( get_option('ewo_enable_change_order_status') );

    
    $to = $admin_email;
    $subject = 'The subject';
    $message = 'The email body content';
    $headers = array('Content-Type: text/html; charset=UTF-8');

    // wp_mail( $to, $subject, $message, $headers );

};
// add_action( 'woocommerce_order_status_changed', 'action_woocommerce_order_status_changed', 10, 4 ); 