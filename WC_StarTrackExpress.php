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
        
        $this->enabled      = $this->get_option( 'enabled'      );
        $this->account_no   = $this->get_option( 'account_no'   );
        $this->access_key   = $this->get_option( 'access_key'   );
        $this->username     = $this->get_option( 'username'     );
        $this->password     = $this->get_option( 'password'     );
        $this->wsdl_file    = $this->get_option( 'wsdl_file'    );
        $this->sender_addr  = $this->get_option( 'sender_addr'  );
        $this->sender_suburb= $this->get_option( 'sender_suburb');
        $this->sender_pcode = $this->get_option( 'sender_pcode' );
        $this->sender_state = $this->get_option( 'sender_state' );
        
        // TODO: validate service preferences
        
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
            'sender_addr'   => array(
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
            // Generate the HTML for the settings form.
            $this->generate_settings_html();
            
            // Get available services from StarTrack
            include_once('eServices/eServices.php');
            include_once('eServices/CustomerConnect.php');

            $connection = array(
                'username'      => $this->username,
                'password'      => $this->password,
                'userAccessKey' => $this->access_key,
                'qsdkFileSpec'  => $this->wsdl_file,
            );
                
            $request = array(
                'parameters' => array(
                    'header' => array(
                        'source'        => 'TEAM',
                        'accountNo'     => $this->account_no,
                        'userAccessKey' => $connection['userAccessKey']
                    )
                )
            );
            
            try {
                $oC = new STEeService();
                $response = $oC->invokeWebService($connection,'getServiceCodes', $request);
            }
            catch (SoapFault $e) {
                $response = false;
                //TODO: add admin message: could not contact StarTrack eServices.
            }
            
            $services = array();
            if($response){
                foreach($response->codes as $code) {
                    if( $code->isDefault) {
                        $services[$code->code] =$code->desc;
                    }
                }
            }
            
            // Generate the HTML for the service preferences form.
            $prefs = $this->wootrack->getTable('service_preferences');
            $pref_meta = $this->wootrack->getTableMeta()['service_preferences'];
            ?>
            
            <tr valign="top">
                <th scope="row" class="titledesc"><?php __('Service preferences', 'wootrack'); ?></th>
                <td class="forminp" id="<?php echo $this->id; ?>_services">
                    <table class="service preferences" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="check-column"><input type="checkbox"></th>
                                <th class="service_code"><?php __('Service Code', 'wootrack'); ?></th>
                                <th class="service_name"><?php __('Serbice Name', 'wootrack'); ?></th>
                                <?php
                                    // foreach($pref_meta['columns']; as $column => $meta){
                                        // if($meta['display']){
                                            // echo "<th class='".$column."'>".$meta['name']."</th>";
                                        // }
                                    // }
                                ?>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan=2>
                                    <select id="select_service">
                                        <option value="">Select a service</option>
                                        <?php
                                            foreach($services as $code => $desc){
                                                //TODO: exclude options already in table
                                                echo "<option value='$code'>$desc</option>";
                                            }
                                        ?>
                                    </select>
                                    <a href="#" class="add button">
                                        <?php '+ '.__('Add service', 'wootrack'); ?>
                                    </a>
                                    <a href="#" class="remove button">
                                        <?php '- '.__('Remove selected services', 'wootrack'); ?>
                                    </a>
                                </th>
                            </tr>
                        </tfoot>
                        <tbody>
                        <?php 
                        foreach($prefs as $pref){
                        ?>
                            <tr class="service">
                                <td class="check-column"><input type="checkbox" name="select" /></td>
                                <td class="service_code"><?php echo pref['code']; ?></td>
                                <td class="service_name"><?php echo pref['name']; ?></td>
                                <?php 
                                    // foreach($pref_meta['columns'] as $column => $meta){
                                        // if($meta['disp']){
                                            // echo "<td class='service_$column'>".$pref[$column]."</td>"  
                                        // }
                                    // }
                                ?>
                            </tr>
                        </tbody>
                    </table><!--/.service-preferences-table-->
                </td>
            </tr>
        </table><!--/.form-table-->
        <script type="text/javascript">
            jQuery(function() {
                // Add service
                jQuery('#<?php echo $this->id; ?>_services').on( 'click', 'a.add', function(){
                    var s = document.getElementById("select_service");
                    var s_code = s.options[s.selectedIndex].value;
                    var s_name = s.options[s.selectedIndex].text;
                    
                    //TODO: check that code is not already in table
                    
                    if( s.localeCompare("") != 0 ){                    
                        jQuery('\
                            <tr class="service">\
                                <td class="check-column"><input type="checkbox" name="select" /></td>\
                                <td class="service_code">' + s_code + '</td>\
                                <td class="service_name">' + s_name + '</td>\
                            </tr>'
                        ).appendTo('#<?php echo $this->id; ?>_services table tbody');
                    }
                    
                    return false;
                });
                
                // Remove service
                jQuery('#<?php echo $this->id; ?>_services').on( 'click', 'a.remove', function(){
                    var answer = confirm("<?php __('Are you sure you want to delete the selected rates?', 'wootrack'); ?>" );
                    if(answer) {
                        jQuery('#<?php echo $this->id; ?>_services table tbody tr td.check-column input:checked').each(function(i, el){
                            jQuery(el).closest('tr').remove();
                        });
                    }
                    
                    return false;
                });
            });
        </script>
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

        // If(WP_DEBUG) error_log('here comes the package:');
        // //error_log(strtr(serialize($package),array(';'=>";\n",'{'=>"{\n",'}'=>"\n}\n")));

        // If(WP_DEBUG) error_log("-> contents:");
        // foreach($package['contents'] as $k => $v){
            // If(WP_DEBUG) error_log(
                // '    ' . implode(', ',
                    // array(
                        // $v['product_id'],
                        // $v['variation_id'],
                        // $v['quantity']
                    // )
                // )
            // );
        // }

        // If(WP_DEBUG) error_log("-> destination: \n    " . serialize($package['destination']));


        // $oConnect = new ConnectDetails();
        // $connection = $oConnect->getConnectDetails();
        
        
        // $request = array(
            // 'parameters' => array(
                // 'header' => array(
                    // 'source'        => 'TEAM',
                    // // 'accountNo'     => '12345',
                    // 'userAccessKey' => $connection['userAccessKey']
                // )
            // )
        // );
        // $oC = new STEeService();
        // $response = $oC->invokeWebService($connection, 'getServiceCodes', $request);
    
        // If(WP_DEBUG) error_log("here come the codes: ");
        $connection = array(
            'username'      => $this->username,
            'password'      => $this->password,
            'userAccessKey' => $this->access_key,
            'qsdkFileSpec'  => $this->wsdl_file,
        );

        foreach($this->service_preferences as $code => $preferences) {
            // if($v->isDefault){
                // If(WP_DEBUG){
                    // error_log(
                        // '    '.
                        // implode(
                            // ', ', 
                            // array(
                                // $v->serviceCode, 
                                // $v->serviceDescription
                            // )
                        // )
                    // );
                // }
            
                                
            // $request = array(
                // 'parameters' => array(
                    // 'header' => array(
                        // 'source'        => 'TEAM',
                        // 'accountNo'     => $this->account_no,
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