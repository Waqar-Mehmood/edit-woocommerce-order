<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

function ewo_manage_order_columns( $column ) {
	if ( $column == 'edit_order' ) {
		$meta_value = get_post_meta( get_the_ID(), 'edit_order_disable', true );
		if( empty( $meta_value ) ) $meta_value = 'no';
		$check_value = checked( 'yes', $meta_value, false );

        echo '
        <div>
			<a href="" style="display: flex; align-items: center;">
				<input type="checkbox" data-productid="' . get_the_ID() .'" class="ewo-order-locked" ' . $check_value . '/>
				<small style="display:block;color:#7ad03a"></small>
			</a>
        <div>';
	}
}

function ewo_add_new_locked_order_columns( $columns ) {
    $columns['edit_order'] = __( 'Order Locked', 'editorder' );
  	return $columns;
}

function ewo_sort_order_function( $columns ) {
	$custom = array(
        'edit_order' => __( 'Order Locked', 'editorder' )
    );
	return wp_parse_args( $custom, $columns );
}

function ewo_orders_filter_by_order_locked_column( $query ) {
	if( empty( $_GET['orderby'] ) || empty( $_GET['order'] ) ) return;

	if( $_GET['orderby'] == 'Order Locked' ) {
		$query->set('meta_key', 'edit_order_disable' );
		$query->set('orderby', 'meta_value'); // or meta_value_num
		$query->set('order', $_GET['order'] );
	}

	return $query;
}

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

function ewo_process_ajax() {

	check_ajax_referer( 'activatingcheckbox', 'myajaxnonce' );

	$prev_value = $_POST['value'] == 'yes' ? 'no' : 'yes';

	if( update_post_meta( $_POST[ 'product_id'] , 'edit_order_disable', $_POST['value'], $prev_value ) ) {
		echo 'Saved';
	}

	die();
}