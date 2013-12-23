<?php
function startrack_express_init() {
    // if( ! class_exists( 'WC_StarTrack_Express' ) ) {
        include('WC_StarTrackExpress.php');
    // }
}

add_action( 'woocommerce_shipping_init', 'startrack_express_init' );

function add_startrack_express( $methods ) {
    $methods[] = 'WC_StarTrack_Express';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_startrack_express' );

error_log( "wootrack shipping registered" );

?>