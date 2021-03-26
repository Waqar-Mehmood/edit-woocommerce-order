<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

add_filter( 'manage_edit-shop_order_columns', 'MY_COLUMNS_FUNCTION' );
function MY_COLUMNS_FUNCTION( $columns ) {
    $columns['edit_order'] = __( 'Order Locked', 'editorder' );
  	return $columns;
}

add_filter( "manage_edit-shop_order_sortable_columns", 'MY_COLUMNS_SORT_FUNCTION' );
function MY_COLUMNS_SORT_FUNCTION( $columns ) {
	$custom = array(
        'edit_order' => __( 'Order Locked', 'editorder' )
    );
	return wp_parse_args( $custom, $columns );
}

add_action( 'pre_get_posts', 'ewo_filter' );
function ewo_filter( $query ) {
	// if it is not admin area, exit the filter immediately
	if ( ! is_admin() ) return;

	if( empty( $_GET['orderby'] ) || empty( $_GET['order'] ) ) return;

	if( $_GET['orderby'] == 'Order Locked' ) {
		$query->set('meta_key', 'edit_order_disable' );
		$query->set('orderby', 'meta_value'); // or meta_value_num
		$query->set('order', $_GET['order'] );
	}

	return $query;

}

add_action( 'manage_shop_order_posts_custom_column', 'MY_COLUMNS_VALUES_FUNCTION', 2 );
function MY_COLUMNS_VALUES_FUNCTION( $column ) {
	if ( $column == 'edit_order' ) {
		$meta_value = get_post_meta( get_the_ID(), 'edit_order_disable', true );
		if( empty( $meta_value ) ) $meta_value = 'no';
		$check_value = checked( 'yes', $meta_value, false );
		$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=ewo_order_locked&check='.$meta_value.'&order_id='.get_the_ID() ), 'ewo_order_locked_status' );

        echo '
        <div>
			<a href="" style="display: flex; align-items: center;">
				<input type="checkbox" data-productid="' . get_the_ID() .'" class="ewo-order-locked" ' . $check_value . '/>
				<small style="display:block;color:#7ad03a"></small>
			</a>
        <div>';
	}
}

add_action( 'admin_footer', 'ewo_jquery_event' );
function ewo_jquery_event(){

	echo "<script>jQuery(function($){
		$('.ewo-order-locked').click(function(e){
			var checkbox = $(this),
			    checkbox_value = (checkbox.is(':checked') ? 'yes' : 'no' );
			$.ajax({
				type: 'POST',
				data: {
					action: 'productmetasave', // wp_ajax_{action} WordPress hook to process AJAX requests
					value: checkbox_value,
					product_id: checkbox.attr('data-productid'),
					myajaxnonce : '" . wp_create_nonce( "activatingcheckbox" ) . "'
				},
				beforeSend: function( xhr ) {
					checkbox.prop('disabled', true );
				},
				url: ajaxurl, // as usual, it is already predefined in /wp-admin
				success: function(data){
					checkbox.prop('disabled', false ).next().html(data).show().fadeOut(500);
				}
			});
		});
	});</script>";

}

// this small piece of code can process our AJAX request
add_action( 'wp_ajax_productmetasave', 'ewo_process_ajax' );
function ewo_process_ajax() {

	check_ajax_referer( 'activatingcheckbox', 'myajaxnonce' );

	if( update_post_meta( $_POST[ 'product_id'] , 'edit_order_disable', $_POST['value'] ) ) {
		echo 'Saved';
	}

	die();
}

/**
 * Add "no-link" class to tr's from WooCommerce orders screen
 * Link: https://github.com/woocommerce/woocommerce/pull/18708
 * Hook reference: https://developer.wordpress.org/reference/hooks/post_class/
 * Tested with: WooCommerce 3.3.1-rc.1
 */
function add_no_link_to_post_class( $classes ) {
	if ( current_user_can( 'manage_woocommerce' ) ) { //make sure we are shop managers
        foreach ( $classes as $class ) {
	        if( $class == 'type-shop_order' ) {
	            $classes[] = 'no-link';
	        }
    	}
    }
    return $classes;
}
// add_filter( 'post_class', 'add_no_link_to_post_class' );