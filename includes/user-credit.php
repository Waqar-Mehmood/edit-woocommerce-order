<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Check user credit
 * Display credit in user dashboard
 */
function ewo_user_credit() {

    $credit = get_user_meta( get_current_user_id(), 'credit', true );
    $currency_symbol = get_woocommerce_currency_symbol();

    if( empty( $credit ) )
        $credit = 0;

    $credit = $currency_symbol . $credit;
    echo '
        <div>
            <p><b>'. __( 'Credit', 'editorder' ) .':</b> '.$credit.'</p>
        </div>';
}