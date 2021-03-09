<?php declare(strict_types = 1);

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Enqueue Style and Script files
 */
function ewo_enqueue() {

    // Register the EWO Style file.
    wp_register_style(
        'ewo-style',
        EWO_PLUGIN_PATH . 'assets/css/ewo-style.css',
        array(),
        EWO_CURRENT_VERSION,
        false
    );

    wp_enqueue_style( 'ewo-style' );

    if( wp_script_is( 'sweetalert' ) == false ) {

        // Register the SweetAlert file.
        // EWO_PLUGIN_PATH . 'assets/js/sweetalert.min.js'
        // https://cdn.jsdelivr.net/npm/sweetalert2@10.15.0/dist/sweetalert2.all.min.js
        wp_register_script(
            'sweetalert',
            'https://cdn.jsdelivr.net/npm/sweetalert2@10.15.0/dist/sweetalert2.all.min.js',
            array(),
            EWO_CURRENT_VERSION,
            false
        );

        wp_enqueue_script( 'sweetalert' );
    }

    wp_register_script(
        'ewo-script',
        EWO_PLUGIN_PATH . 'assets/js/ewo-script.js',
        array(),
        EWO_CURRENT_VERSION,
        true
    );

    wp_enqueue_script( 'ewo-script' );
}

add_action( 'wp_enqueue_scripts', 'ewo_enqueue', 999999 );