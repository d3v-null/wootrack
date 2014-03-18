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
        
        $this->service_pref_option  = $this->id.'_service_preferences';

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_service_preferences' ) );
        
        
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
        $this->p_path       = $this->get_option( 'protected_path');
        $this->wsdl_file    = $this->get_option( 'wsdl_file'    );
        
        // $this->sender_addr  = $this->get_option( 'sender_addr'  );
        // $this->sender_suburb= $this->get_option( 'sender_suburb');
        // $this->sender_state = $this->get_option( 'sender_state' );
        
        $this->connection   = array(
            'username'      => $this->username,
            'password'      => $this->password,
            'userAccessKey' => $this->access_key,
            'wsdlFilespec'  => $this->p_path . $this->wsdl_file,
        );
		If(WP_DEBUG) error_log( "Connection: ".serialize($this->connection) );
		
        $this->header = array(
            'source'        => 'TEAM',
            'accountNo'     => $this->account_no,
            'userAccessKey' => $this->connection['userAccessKey']
        );
		If(WP_DEBUG) error_log( "Header: ".serialize($this->header) );
		
        $this->sender_location = array(
			'postCode' 		=> $this->get_option( 'sender_pcode' ),
			'state'         => "",
			'suburb'        => "",
		);
		
		// Validate sender location
		$request = array(
			'parameters' => array(
				'header'    => $this->header,
				'address'   => $this->sender_location,
			)
		);
		try {
			If(WP_DEBUG) error_log( "validating postCode from settings: " . serialize($request));
			$oC = new STEeService();
			$response = $oC->invokeWebService($this->connection,'validateAddress', $request);
			
			If(WP_DEBUG) error_log( "response: " . serialize($response) );
			//fill in sender location with first matched location
			if($response->matchedAddress) {
				$this->sender_location['suburb'] = $response->matchedAddress[0]->suburbOrLocation;
				$this->sender_location['state']  = $response->matchedAddress[0]->state;
			}   
		}
		catch (SoapFault $e) {
			If(WP_DEBUG) error_log( "Soapfault! " . serialize( get_object_vars ($e) ) );
			$response = false;
			//TODO: add admin message: could not contact StarTrack eServices.
		}       		
		
		
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
            'protected_path'=> array (
                'title'         => __('Protected path', 'wootrack'),
                'type'          => 'text',
                'description'   => __('location of protected directory where sensitive config files are stored'),
                'desc_tip'      => true,
                'default'       => '/public_html/cgi_bin/',
            ),
            // 'forced_SSL_ver'=> array{
                // 'title'         => __('Forced SSL version', 'wootrack'),
                // 'type'          => 'text',
                // 'description'   => __('Version of SSL to use when communicating with Star Track'),
                // 'desc_tip'      => true,
                // 'default'       => '3',
            // ),
            'wsdl_file'     => array(
                'title'         => __('WSDL File Spec', 'wootrack'),
                'type'          => 'text',
                'description'   => __('Location of the WSDL XML file within the protected path', 'wootrack'),         
                'desc_tip'      => true,
                'default'       => 'C:\xampp\cgi-bin\eServicesStagingWSDL.xml'
            ),
            // 'sender_addr'   => array(
                // // 'class'         => 'Sender\'s location',            
                // 'title'         => __('Sender\'s Address', 'wootrack'),
                // 'type'          => 'text',
                // // 'description'   => 'Location of the WSDL XML file',                
                // 'default'       => ''
            // ),                
            // 'sender_suburb' => array(
                // // 'class'         => 'Sender\'s location',
                // 'title'         => __('Sender\'s Suburb', 'wootrack'),
                // 'type'          => 'text',
                // // 'description'   => '',
                // 'default'       => ''
            // ),
            'sender_pcode'  => array(
                // 'class'         => 'Sender\'s location',
                'title'         => __('Sender\'s Post Code', 'wootrack'),
                'type'          => 'text',
                'description'   => __('Postcode of the location which packages are dispatched from', 'wootrack'),
                'desc_tip'      => true,                
                'default'       => ''
            ),
            // 'sender_state'  => array(
                // // 'class'         => 'Sender\'s location',
                // 'title'         => __('Sender\'s State', 'wootrack'),
                // 'type'          => 'text',
                // // 'description'   => '',                
                // 'default'       => ''
            // )
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
            
            try {
                $oC = new STEeService();
                $response = $oC->invokeWebService(
                    $this->connection,
                    'getServiceCodes',
                    array(
                        'parameters' => array(
                            'header' => $this->header
                        )
                    )
                );
            }
            catch (SoapFault $e) {
                $response = false;
                //TODO: add admin message: could not contact StarTrack eServices.
            }
            
            $services = array();
            if($response){
                foreach($response->codes as $code) {
                    if( $code->isDefault) {
                        $services[$code->serviceCode] =$code->serviceDescription;
                    }
                }
            }
            
            // Generate the HTML for the service preferences form.
            // $prefs = $this->wootrack->getTable('service_preferences');
            $prefs = get_option($this->service_pref_option, false);
            
            ?>
			
			<tr valign="top">
				<th colspan scope="row" class="titledesc"><?php _e('Settings validation', 'wootrack'); ?></th>
                <td>
                    <p><strong><?php echo __('Protected path', 'wootrack') . '<br>'; ?></strong>
                    <?php 
                        $p_path = $this->get_option('protected_path');
                        // $status = [
                            // 'is_dir'        => is_dir(      $path),
                            // 'is_readable'   => is_readable( $path),
                            // 'is_writeable'  => is_writeable($path),
                        // ];
                        echo __('Is a valid directory', 'wootrack') . ': ' . (is_dir(      $p_path)?'Y':'N') . '<br>';
                        echo __('we have read access',  'wootrack') . ': ' . (is_readable( $p_path)?'Y':'N') . '<br>';
                        echo __('we have write access', 'wootrack') . ': ' . (is_writeable($p_path)?'Y':'N') ;
                    ?></p>
                    <p><strong><?php echo __('Connection to eServices API', 'wootrack') . '<br>'; ?></strong>
                    <?php
                        echo $response?'Y':'N';
                    ?></p>
                    <p><strong><?php echo __('Matched Suburb', 'wootrack') . '<br>'; ?></strong>
                    <?php
                        if($this->sender_location['suburb'] != '') {
                            echo( $this->sender_location['suburb'].", ".$this->sender_location['state'] );
                        } else {
                            _e('No matched location. Press save settings to refresh', 'wootrack');
                        }
                    ?></p>
                </td>
			</tr>           
			
            
            <tr valign="top">
                <th scope="row" class="titledesc"><?php _e('Service preferences', 'wootrack'); ?></th>
                <td class="forminp" id="<?php echo $this->id; ?>_services">
                    <table class="shippingrows widefat" cellspacing="0">
                        <thead>
                            <tr>
                                <th class="check-column"><input type="checkbox"></th>
                                <th class="service_code"><?php _e('Service Code', 'wootrack'); ?></th>
                                <th class="service_name"><?php _e('Service Name', 'wootrack'); ?></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan=3>
                                    <select id="select_service">
                                        <option value="">Select a service</option>
                                        <?php
                                            foreach($services as $code => $desc){
                                                //TODO: exclude options already in table
                                                echo "<option value='$code'>$desc</option>";
                                            }
                                        ?>
                                    </select>
                                    <a class="add button"> <?php _e('Add service', 'wootrack'); ?></a>
                                    <a class="remove button"><?php _e('Remove selected services', 'wootrack'); ?></a>
                                </th>
                            </tr>
                        </tfoot>
                        <tbody>
                        <?php 
                        $i = -1;
                        if($prefs) foreach($prefs as $code => $name){
                            $i++;
                        ?>
                            <tr class="service">
                                <td class="check-column">
                                    <input type="checkbox" name="select" />
                                </td>
                                <td class="service_code">
                                    <input type="text" value="<?php echo $code; ?>" readonly="readonly"
                                           name="<?php echo esc_attr( $this->id.'_code['.$i.']' ); ?>" />                                    
                                </td>
                                <td class="service_name">
                                    <input type="text" value="<?php echo $name; ?>"
                                           name="<?php echo esc_attr( $this->id.'_name['.$i.']' ); ?>" />
                                </td>
                            </tr>
                        <?php 
                        } 
                        ?>
                        </tbody>
                    </table><!--/.service-preferences-table-->
                </td>
            </tr>
        </table><!--/.form-table-->
        <script type="text/javascript">
            jQuery(function() {
                // Add service
                jQuery('#<?php echo $this->id; ?>_services').on( 'click', 'a.add', function(){
                    var size = jQuery('#<?php echo $this->id; ?>_services tbody .service').size();
                    
                    var s = document.getElementById("select_service");
                    var s_code = s.options[s.selectedIndex].value;
                    var s_name = s.options[s.selectedIndex].text;
                    
                    //TODO: check that code is not already in table
                    
                    if( s_code.localeCompare("") != 0 ){          
                        jQuery('\
                            <tr class="service">\
                                <td class="check-column">\
                                    <input type="checkbox" name="select" />\
                                </td>\
                                <td class="service_code">\
                                    <input type="text" value="' + s_code + '" readonly="readonly"\
                                           name="<?php echo $this->id; ?>_code[' + size + ']" />\
                                </td>\
                                <td class="service_name">\
                                    <input type="text" value="' + s_name + '"\
                                           name="<?php echo $this->id; ?>_name[' + size + ']" />\
                                </td>\
                            </tr>'
                        ).appendTo('#<?php echo $this->id; ?>_services table tbody');
                    }
                    
                    return false;
                });
                
                // Remove service
                jQuery('#<?php echo $this->id; ?>_services').on( 'click', 'a.remove', function(){
                    var answer = confirm("<?php _e('Are you sure you want to delete the selected rates?', 'wootrack'); ?>" );
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
 
    function process_service_preferences() {
        $service_pref_code  = array();
        $service_pref_name  = array();
        $service_prefs      = array();
        
        if( isset( $_POST[ $this->id . '_code'] ) ) $service_pref_code = array_map( 'woocommerce_clean', $_POST[ $this->id.'_code'] );
        if( isset( $_POST[ $this->id . '_name'] ) ) $service_pref_name = array_map( 'woocommerce_clean', $_POST[ $this->id.'_name'] );
        
        foreach($service_pref_code as $key => $code){
            $service_prefs[$code] = $service_pref_name[$key];
        }
        
        update_option($this->service_pref_option, $service_prefs);
    }    
    
    //TODO: postcode and username validation
    
    public function calculateShippingParams($contents){
        //default values for parameters
        $params = array(
            'weight'    => 1,
            'volume'    => 0.1,
            'noOfItems' => 1,
        );
        foreach($contents as $line){
            if($line['data']->has_weight()){
                $params['weight'] += $line['quantity'] * $line['data']->get_weight();
            } else {
                // throw exception because can't get weight
            }
            if($line['data']->has_dimensions()){
                $dimensions = explode(' x ', $line['data']->get_dimensions());
                $dimensions[2] = str_replace( ' '.get_option( 'woocommerce_dimension_unit' ), '', $dimensions[2]); 
                $params['volume'] += $line['quantity'] * array_product( $dimensions ) / 1000000;
            } else {
                // throw exception because can't get dimensions
            }
        }
        
        If(WP_DEBUG) error_log( "Shipping params: \n".serialize($params) );
        
        return $params;
    }
        
    
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

        If(WP_DEBUG) error_log("-> destination: \n    " . serialize($package['destination']));
        
        $destination = $package['destination'];
        
        if($destination['country'] == 'AU'){

			// Validate sender location
			// $request = array(
				// 'parameters' => array(
					// 'header'    => $this->header,
					// 'address'   => $this->sender_location,
				// )
			// );
			// try {
				// $oC = new STEeService();
				// $response = $oC->invokeWebService($this->connection,'validateAddress', $request);
				
				// //fill in sender location with first matched location
				// if($response->matchedAddress) {
				// }   
			// }
			// catch (SoapFault $e) {
				// $response = false;
				// //TODO: add admin message: could not contact StarTrack eServices.
			// }            
			
			// If(WP_DEBUG) error_log( "Validating sender location: \n".serialize($response) );     
					
            $receiverLocation = array(
                // 'addressLine'   => $destination['address'],
                // 'suburb'        => $destination['city'],
                'postCode'      => $destination['postcode'],
                'state'         => strtoupper($destination['state'])
            );
            
            // Validate receiver location
            $request = array(
                'parameters' => array(
                    'header'        => $this->header,
                    'address'       => $receiverLocation
                )
            );
            try {
                $oC = new STEeService();
                $response = $oC->invokeWebService($this->connection,'validateAddress', $request);
				
				$receiverLocation['suburb'] = $response->matchedAddress[0]->suburbOrLocation;
				$receiverLocation['state']  = $response->matchedAddress[0]->state;
            }
            catch (SoapFault $e) {
                $response = false;
                //TODO: add admin message: could not contact StarTrack eServices.
            }            
            
            If(WP_DEBUG) error_log( "Validating receiver location: \n".serialize($response) ); 
            
            $prefs = get_option($this->service_pref_option, false);
            
            if($prefs) {
                $params = $this->calculateShippingParams($package['contents']);

                foreach($prefs as $code => $name) {                                
                    $request = array(
                        'parameters' => array(
                            'header'            => $this->header,
                            'senderLocation'    => $this->sender_location,
                            'receiverLocation'  => $receiverLocation,
                            'serviceCode'       => $code,
                            'noOfItems'         => $params['noOfItems'], 
							// TODO uncomment these
                            'weight'            => $params['weight'   ],
                            // 'weight'            => 5.0,
                            //THIS IS IN CM
                            'volume'            => $params['volume'   ],
                            // 'volume'            => 0.01,
                        )
                    );
					
					If(WP_DEBUG) error_log( "request: \n".serialize($request) );
					If(WP_DEBUG) error_log( "connection: \n".serialize($this->connection) );

                    try {
                        $oC = new STEeService();
                        $response = $oC->invokeWebService($this->connection,'calculateCost', $request);
                        $this->add_rate(
                            array(
                                'id'        => $code,
                                'label'     => $name,
                                'cost'      => $response->cost,
                                'calc_tax'  => 'per_item'
                            )
                        );
                    }
                    catch (SoapFault $e) {
                        $response = false;
                        If(WP_DEBUG) error_log( "Exception in calculateCost, " . $e );
                        If(WP_DEBUG) error_log( "details: " . $e->getMessage() );
						//TODO: add admin message: could not contact StarTrack eServices.
                    }
                    
                    If(WP_DEBUG) error_log( "response: \n".serialize($response) );
                    // If(WP_DEBUG) error_log( 'request: '.serialize($request).'\n response: '.serialize($response) );
                }
            } else {
				If(WP_DEBUG) error_log( "no prefs" );
			}
        }
    }
}

?>