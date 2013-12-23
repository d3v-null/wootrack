<?php
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

        error_log('here comes the package:');
        //error_log(strtr(serialize($package),array(';'=>";\n",'{'=>"{\n",'}'=>"\n}\n")));

        error_log("-> contents:");
        foreach($package['contents'] as $k => $v){
            error_log(
                '    ' . implode(', ',
                    array(
                        $v['product_id'],
                        $v['variation_id'],
                        $v['quantity']
                    )
                )
            );
        }

        error_log("-> destination: \n    " . serialize($package['destination']));


        $oConnect = new ConnectDetails();
        $connection = $oConnect->getConnectDetails();
        $request = array(
            'parameters' => array(
                'header' => array(
                    'source'        => 'TEAM',
                    // 'accountNo'     => '12345',
                    'userAccessKey' => $connection['userAccessKey']
                )
            )
        );
        $oC = new STEeService();
        $response = $oC->invokeWebService($connection, 'getServiceCodes', $request);



        // foreach($response->codes as $k => $v) {
            // if($v->isDefault){
                // $request = array(
                    // 'parameters' => array(
                        // 'header' => array(
                            // 'source'        => 'TEAM',
                            // 'accountNo'     => '12345',
                            // 'userAccessKey' => $connection['userAccessKey']
                        // ),
                        // 'senderLocation' => array(
                            // 'suburb' => $_POST['suburbSender'],
                            // 'postCode' => $_POST['postCodeSender'],
                            // 'state' => strtoupper($_POST['stateSender'])		// Must be upper case
                        // ),
                        // 'receiverLocation' => array(
                            // 'suburb' => $_POST['suburbReceiver'],
                            // 'postCode' => $_POST['postCodeReceiver'],
                            // 'state' => strtoupper($_POST['stateReceiver'])	// Must be upper case
                        // ),
                        // 'serviceCode' => $v->serviceCode,
                        // 'noOfItems' => $_POST['noOfItems'],
                        // 'weight' => $_POST['weight'],
                        // 'volume' => $_POST['volume']
                    // )
                // );




                // $this->add_rate(
                    // $cost
                    // array(
                        // 'id'    => $v->serviceCode,
                        // 'label' => $v->serviceDescription,
                        // '
            // }
        // }

        $rate = array(
            'id'        => $this->id,
            'label'     => $this->title,
            'cost'      => '100.99',
            'calc_tax'  => 'per_item'
        );

        // Register the rate
        $this->add_rate( $rate );
    }
}

?>