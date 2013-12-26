<?php
class WC_StarTrack_Express extends WC_Shipping_Method {
    /**
     * Constructor for StarTrack shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
        require_once('Wootrack_Plugin.php');
        $this->wootrack = new Wootrack_Plugin();
        
        $this->id                   = 'StarTrack_Express'; // Id for shipping method.
        $this->method_title         = __( 'StarTrack Express' );  // Title shown in admin
        $this->method_description   = __( 'Send by StarTrack Express road freight' ); // Description shown in admin

        $this->enabled              = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title                = "StarTrack Express"; // This can be added as an setting but for this example its forced.

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        // add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_service_preferences' ) );
        
        
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
        
        $this->enabled      = $this->get_option( 'enabled' );
        $this->account_no   = $this->get_option( 'account_no' );
        $this->access_key   = $this->get_option( 'access_key' );
        $this->username     = $this->get_option( 'username' );
        $this->password     = $this->get_option( 'password' );
        $this->wsdl_file    = $this->get_option( 'wsdl_file' );
        // TODO: get sender's location
        
        // TODO: get service preferences

    }
    
    /**
     * Initialise Gateway Settings Form Fields
     */
    
    function init_form_fields() {
        $this->form_fields = array(
            'enabled'       => array(
                'title'         => __('Enable/Disable', 'woocommerce'),
                'type'          => 'checkbox',
                'label'         => __('Enable this shipping method', 'woocommerce'),
                // 'description'   => '',
                'default'       => 'no'
            ),
            'account_no'    => array(
                // 'class'         => 'StarTrack Account',
                'title'         => __('StarTrack Account Number', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',
                'default'       => '12345'
            ),
            'access_key'    => array(
                // 'class'         => 'StarTrack Account',
                'title'         => __('StarTrack Access Key', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => '30405060708090'
            ), 
            'username'      => array(
                // 'class'         => 'StarTrack Account',
                'title'         => __('StarTrack Username', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => 'TAY00002'
            ),
            'password'      => array(
                // 'class'         => 'StarTrack Account',
                'title'         => __('StarTrack Password', 'wootrack'),
                'type'          => 'password',
                // 'description'   => '',                
                'default'       => 'Tay12345'
            ),
            'wsdl_file'     => array(
                'title'         => __('WSDL File Spec', 'wootrack'),
                'type'          => 'text',
                'description'   => 'Location of the WSDL XML file',                
                'default'       => 'C:\xampp\cgi-bin\eServicesStagingWSDL.xml'
            ),
            'sender_address'=> array(
                // 'class'         => 'Sender\'s location',            
                'title'         => __('Sender\'s Address', 'wootrack'),
                'type'          => 'text',
                // 'description'   => 'Location of the WSDL XML file',                
                'default'       => 'C:\xampp\cgi-bin\eServicesStagingWSDL.xml'
            ),                
            'sender_suburb' => array(
                // 'class'         => 'Sender\'s location',
                'title'         => __('Sender\'s Suburb', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',
                'default'       => ''
            ),
            'sender_pcode'  => array(
                // 'class'         => 'Sender\'s location',
                'title'         => __('Sender\'s Post Code', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => ''
            ),
            'sender_state'  => array(
                // 'class'         => 'Sender\'s location',
                'title'         => __('Sender\'s State', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => ''
            )
        );
    }
    
    // public function validate_settings_fields( $form_fields = false ){
        // parent::validate_settings_fields($form_fields);
        
    // }
    
    public function admin_options() {
        global $woocommerce;
        ?>
        <h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>
        <?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>
        <table class="form-table">
        <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc"><?php __('Service preferences', 'wootrack');?></th>
                <td class="forminp" id="<?php echo $this->id; ?>_services">
                    <table cellspacing="0"><!-- no class -->
                        <thead>
                            <tr>
                                <th class="check-column"><input type="checkbox"></th>
                                <?php
                                    $columns = $wootrack->getTableMetadata()['service_preferences']['columns'];
                                    foreach($columns as $column => $meta){
                                        if($meta['display']){
                                            echo "<th class='".$column."'>".$meta['name']."</th>";
                                        }
                                    }
                                ?>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan=2>
                                    <a href="#" class="add button">
                                        <?php '+ '.__('New service', 'wootrack'); ?>
                                    </a>
                                    <a href="#" class="remove button">
                                        <?php '- '.__('Remove selected services', 'wootrack'); ?>
                                    </a>
                                </th>
                            </tr>
                        </tfoot>
                        <tbody><!-- no class -->
                        <?php 
                            //TODO: Autogenerate rows from db table
                            
                        ?>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table><!--/.form-table-->
        <?php
    }
    
    //TODO: write process_shipping_preferences
    public function process_shipping_preferences() {
        
    
    
    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package ) {

        If(WP_DEBUG) error_log('here comes the package:');
        //error_log(strtr(serialize($package),array(';'=>";\n",'{'=>"{\n",'}'=>"\n}\n")));

        If(WP_DEBUG) error_log("-> contents:");
        foreach($package['contents'] as $k => $v){
            If(WP_DEBUG) error_log(
                '    ' . implode(', ',
                    array(
                        $v['product_id'],
                        $v['variation_id'],
                        $v['quantity']
                    )
                )
            );
        }

        If(WP_DEBUG) error_log("-> destination: \n    " . serialize($package['destination']));


        // $oConnect = new ConnectDetails();
        // $connection = $oConnect->getConnectDetails();
        
        $connection = array(
            'username'      => $this->get_option('Username'),
            'password'      => $this->get_option('Password'),
            'userAccessKey' => $this->get_option('AccessKey'),
            'wsdlFilespec'  => $this->get_option('wsdlFile')   
        );
        
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
    
        If(WP_DEBUG) error_log("here come the codes: ");

        foreach($response->codes as $k => $v) {
            if($v->isDefault){
                If(WP_DEBUG){
                    error_log(
                        '    '.
                        implode(
                            ', ', 
                            array(
                                $v->serviceCode, 
                                $v->serviceDescription
                            )
                        )
                    );
                }
            
            
                    
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
            }
        }

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