<?php
function startrack_express_init() {
    if( ! class_exists( 'WC_StarTrack_Express' ) ) {
        class WC_StarTrack_Express extends WC_Shipping_Method {
            /**
             * Constructor for StarTrack shipping class
             *
             * @access public
             * @return void
             */
            public function __construct() {
                $this->id                 = 'StarTrack_Express'; // Id for shipping method. 
                $this->method_title       = __( 'StarTrack Express' );  // Title shown in admin
                $this->method_description = __( 'Send by StarTrack Express road freight' ); // Description shown in admin

                $this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
                $this->title              = "StarTrack Express"; // This can be added as an setting but for this example its forced.

                $this->init();
            }

            /**
             * Init Settings
             *
             * @access public
             * @return void
             */
            function init() {
                // Load the settings API
                $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                // Save settings in admin if you have any defined
                add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
            }

            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping( $package ) {
                $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => '10.99',
                    'calc_tax' => 'per_item'
                );

                // Register the rate
                $this->add_rate( $rate );
            }
        }
    }       
}

add_action( 'woocommerce_shipping_init', 'startrack_express_init' );

function add_startrack_express( $methods ) {
    $methods[] = 'WC_StarTrack_Express';
    return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_startrack_express' );
       
?>