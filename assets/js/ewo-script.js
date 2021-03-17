"use strict";

document.addEventListener("DOMContentLoaded", function(event) {

    const cancel_btn = document.querySelector( '.woocommerce-button.cancel' );
    if( cancel_btn ) {
        cancel_btn.remove();
    }

    const edit_order_buttons = document.querySelectorAll( '.edit-order' );

    edit_order_buttons.forEach(element => {
        element.addEventListener( 'click', function( e ) {

            // Swal.fire({
            //     icon: 'info',
            //     title: 'Oops...',
            //     text: `<?php // $this->get_popup_message(); ?>`,
            // });

            e.preventDefault();
            let popup_id = jQuery( this ).attr( 'href' ).replace("#", "");

            // Get the modal
            let modal = document.getElementById( `${ popup_id }` );
            modal.style.display = 'block';

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function( event ) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        } );

    });

    if( window.location.hash ) {
        document.querySelector( `.edit-order[href='${window.location.hash}']` ).click();
    }

});