<?php
global $woocommerce;

class WC_StarTrack_Express extends WC_Shipping_Method {
    /** @var array Array of validation successes. */
    // public $validations = array();
    
    public $_class = "WC_STE_";
    
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

        // $this->enabled              = "yes"; // This can be added as an setting but for this example its forced enabled
        $this->title                = "StarTrack Express"; // This can be added as an setting but for this example its forced.
        
        $this->service_pref_option  = $this->id.'_service_preferences';
        $this->matched_suburb_option = $this->id.'_matched_suburb';
        $this->matched_state_option = $this->id.'_matched_state';        

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'process_service_preferences' ) );
        // add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'check_secure_path' ) );
        // add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'check_wsdl_file' ) );
        // add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'check_sender_pcode' ) );
        // add_action( 'woocommerce_before_checkout_form', array( $this, 'check_startrack_connection' ) );
       
        
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
        
        $this->enabled      =  $this->get_option( 'enabled'          );
        $this->s_path       =  $this->get_option( 'secure_path'      );
        $this->wsdl_file    =  $this->get_option( 'wsdl_file'        );
        
        $this->connection   = array(
            'username'      => $this->get_option( 'username'         ),
            'password'      => $this->get_option( 'password'         ),
            'userAccessKey' => $this->get_option( 'access_key'       ),
            'wsdlFilespec'  => $this->s_path . $this->wsdl_file,
        );
		
        $this->header = array(
            'source'        => 'TEAM',
            'accountNo'     => $this->get_option( 'account_no'       ),
            'userAccessKey' => $this->connection[ 'userAccessKey']
        );
		
        $this->sender_location = array(
			'postCode' 		=> $this->get_option( 'sender_pcode'     ),
			// 'state'         => $this->get_option( $this->matched_state_option, "NOTSET" ),
			// 'suburb'        => $this->get_option( $this->matched_suburb_option, "NOTSET" ),
			'state'         => $this->get_option( 'sender_state'     ),
			'suburb'        => $this->get_option( 'sender_suburb'    ),
		);

        $this->forced_SSL_ver = $this->get_option('forced_SSL_ver'   );
        
		include_once('eServices.php');
        $this->oC = new STEeService($this->s_path, $this->wsdl_file, $this->forced_SSL_ver);
    }

    public function is_connected(){
        $_procedure = $this->_class."INVOKEWEBSERVICE: ";

        if(isset($this->bad_environment) and $this->bad_environment){
            //skip connection check, bad environment
            $this->connected = false;
        }

        if(!isset($this->connected)){
            $this->connected = $this->check_startrack_connection();
        }
        return $this->connected;
    }

    public function invokeWebService($operation, $request = NULL)
    // wrapper for startrack's invokeWebService
    {
        $_procedure = $this->_class."INVOKEWEBSERVICE: ";

        if(isset($this->connected) and !$this->connected){
            if(WOOTRACK_DEBUG) error_log($_procedure."abborting because of previous failed connection");
            return;
        }

        if(!$this->oC) {
            if(WOOTRACK_DEBUG) error_log($_procedure."cannot invokeWebService with no \$oC");
            return;
        }
        if(!$this->connection){
            if(WOOTRACK_DEBUG) error_log($_procedure."cannot invokeWebService with no \$connection");
            return;
        }
        if(!$operation){
            if(WOOTRACK_DEBUG) error_log($_procedure."cannot invokeWebService with no \$operation");
            return;
        }
        if(!$request or !is_array($request)){
            if(!$this->header){
                if(WOOTRACK_DEBUG) error_log($_procedure."cannot invokeWebService with no \$header");
                return;
            }
            $request = array(
                'parameters' => array(
                    'header' => $this->header
                )
            );
        }

        try {
            if(WOOTRACK_DEBUG) {
                error_log($_procedure."making soapcall");
                error_log($_procedure."username: ". $this->connection['username']);
                error_log($_procedure."password: ". $this->connection['password']);
                error_log($_procedure."operation: ".serialize($operation));
                error_log($_procedure."request: ".serialize($request));
            }
            $response = $this->oC->invokeWebService(
                $this->connection,
                $operation,
                $request
            );
        } catch (SoapFault $e) {
            if(WOOTRACK_DEBUG){
                error_log($_procedure."Caught soapfault: ");
                error_log($_procedure."faultcode: " . $e->faultcode);
                error_log($_procedure."faultstring: " . $e->faultstring);
            } 
            throw new SoapFault($e->faultcode, $e->faultstring, NULL, "");//$e->detail);
            $this->connected = false;
            $response = NULL;
        }
        return $response;
    }    

    function check_startrack_connection(){
        try {
            $response = $this->invokeWebService( 'getServiceCodes' );
        }
        catch (SoapFault $e) {
            if(WOOTRACK_DEBUG) error_log("could not connect to starTrack eServices: ".$e);
            $notice_string = "Unfortunately our StarTrack shipping service is temporarily unavailable. If you would like to ship your order with Startrack, plesae <a href='contact us'>contact our office</a> to place your order";
            if(!is_admin()) wc_print_notice($notice_string, 'notice');
            return false;
        }
        return true;
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
            'secure_path'   => array (
                'title'         => __('Secure path', 'wootrack'),
                'type'          => 'text',
                'description'   => __('location of secure directory where starTrack files are stored (slash-terminated)'),
                'desc_tip'      => true,
                'default'       => '~/public_html/cgi_bin/',
            ),
            'wsdl_file'     => array(
                'title'         => __('WSDL File Spec', 'wootrack'),
                'type'          => 'text',
                'description'   => __('Location of the WSDL XML file within the secure path', 'wootrack'),
                'desc_tip'      => true,
                'default'       => 'eServicesStagingWSDL.xml'
            ),
            'account_no'    => array(
                'title'         => __('StarTrack Account Number', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',
                'default'       => '12345'
            ),
            'access_key'    => array(
                'title'         => __('StarTrack Access Key', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => '30405060708090'
            ), 
            'username'      => array(
                'title'         => __('StarTrack Username', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => 'TAY00002'
            ),
            'password'      => array(
                'title'         => __('StarTrack Password', 'wootrack'),
                'type'          => 'text',
                // 'description'   => '',                
                'default'       => 'Tay12345'
            ),
            'forced_SSL_ver'=> array(
                'title'         => __('Forced SSL version', 'wootrack'),
                'type'          => 'text',
                'description'   => __('Version of SSL to use when communicating with Star Track'),
                'desc_tip'      => true,
                'default'       => '3',
            ),
            'sender_pcode'  => array(
                'title'         => __('Sender\'s Post Code', 'wootrack'),
                'type'          => 'text',
                'description'   => __('Postcode of the location which packages are dispatched from', 'wootrack'),
                'desc_tip'      => true,                
                'default'       => '6000'
            ),
            // 'check_rate'    => array(
            //     'title'         => __('Connection Check Rate', 'wootrack'),
            //     'type'          => 'text',
            //     'description'   => __('The rate at which the startrack connection is checked', 'wootrack'),
            //     'default'       => 10
            // ),
        );
    }
    
    public function validate_settings_fields( $form_fields = array() ){
        parent::validate_settings_fields($form_fields);

        // $this->environment_check();
    }
    
    public function generate_debug_html() {
    try {
        $response = $this->invokeWebService('getServiceCodes');
    }
    catch (SoapFault $e) {
        if(WOOTRACK_DEBUG) error_log("could not connect to starTrack eServices: ".$e);
        //TODO: add admin message: could not contact StarTrack eServices.
    }
?>
<tr valign="top">
    <th colspan scope="row" class="titledesc"><?php _e('Settings validation', 'wootrack'); ?></th>
    <td>
        <p><strong><?php echo __('Secure path', 'wootrack').': '.$this->s_path.'<br>'; ?></strong>
        <?php 
            // $status = [
                // 'is_dir'        => is_dir(      $path),
                // 'is_readable'   => is_readable( $path),
                // 'is_writeable'  => is_writeable($path),
            // ];
            echo __('Is a valid directory', 'wootrack') . ': ' . (is_dir(      $this->s_path)?'Y':'N') . '<br>';
            echo __('we have read access',  'wootrack') . ': ' . (is_readable( $this->s_path)?'Y':'N') . '<br>';
            echo __('we have write access', 'wootrack') . ': ' . (is_writeable($this->s_path)?'Y':'N') ;
        ?></p>
        <p><strong><?php echo __('WSDL File', 'wootrack').': '.$this->s_path.$this->wsdl_file.'<br>'; ?></strong>
        <?php
            echo 'readable: '.(is_readable($this->s_path.$this->wsdl_file)?'Y':'N');
        ?></p>
        <p><strong><?php echo __('eServices API', 'wootrack') . '<br>'; ?></strong>
        <?php
            echo 'connection: '.($response?'Y':'N');
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
<?         
    }

    public function generate_services_html() {        
        try {
            $response = $this->invokeWebService('getServiceCodes');
        }
        catch (SoapFault $e) {
            if(WOOTRACK_DEBUG) error_log("could not connect to starTrack eServices: ".$e);
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
        <?php
    }

    public function generate_sidebar_html(){
        ?>
<div id="wootrack-sidebar" style="float:right; width:20%;">
    <div class="wootrack-section">
        <div class="wootrack-section-title stuffbox">
            <h3><?php _e('About this Plugin', 'wootrack'); ?></h3>
        </div>
        <div class="wootrack-inputs">
            <ul>
                <li>
                    <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2PF5FGAHHBFU2&lc=AU&item_name=Laserphile%20Developers&currency_code=AUD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted"><img
                            width="16" height="16" src="<?php 
                                $plugin_url = WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__));
                                echo $plugin_url ?>/images/pp_favicon_x.ico"><?php _e('Donate with Paypal', 'wootrack'); ?></a></li>
                </li>
            </ul>
        </div>
    </div>
</div>        
        <?php
    }

    public function admin_options() {
        global $woocommerce;
        ?>
<div id="wrapper" style="overflow:auto">
    <div id="main" style="float:left; width:80%">
        <h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>
        <?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>
        <table class="form-table">
        <?php
            $this->environment_check();

            // Generate the HTML for the settings form.
            $this->generate_settings_html();

            if(WOOTRACK_DEBUG) $this->generate_debug_html();
            
            // Get available services from StarTrack and display them
            if($this->is_connected()) $this->generate_services_html();  
        ?>
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
</tr>').appendTo('#<?php echo $this->id; ?>_services table tbody');
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
                
                //Highlight Errors
                <?php 
                    foreach ($this->errors as $k => $v) {
                ?> 
                        jQuery("input#<?php echo $this->plugin_id.$this->id.'_'.$k; ?>").append("<?php echo $v; ?>");
                <?php
                    }
                ?>
                          
            });
        </script>
    </div><!--/main-->
    <div id="wootrack-sidebar" style="float:right; width:20%;">
        <?php $this->generate_sidebar_html(); ?>
    </div><!--/wootrack-sidebar-->
</div><!--/wrapper-->
        <?php
    }  
    
    function admin_notice(){
        If(WOOTRACK_DEBUG) error_log("ADMIN NOTICE CALLED errors: ".serialize($this->errors));
    
        echo '<div class="error">';
        foreach($this->errors as $k => $v){
            echo '<p>'.$v.'</p>';
        }
        echo '<p>derp</p>';
        echo '</div>';
    }

    public function get_admin_notice($notice) {
        return '<div class="error">
                <p>' . $notice . '</p>
            </div>';
    }

    /**
     * environment_check function.
     *
     * @access public
     * @return void
     */
    private function environment_check() {
        global $woocommerce;

        if ( get_woocommerce_currency() != "AUD" ) {
            $message = __( 'Startrack Shipping Method requires that the currency is set to Australian Dollars.', 'wootan' );
            // $this->errors['australian_currency'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        }

        elseif ( $woocommerce->countries->get_base_country() != "AU" ) {
            $message = __( 'Startrack Shipping Method requires that the base country/region is set to Australia.', 'wootan' );
            // $this->errors['australian_base_country'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        }

        elseif ( !$this->sender_location['postCode'] and $this->enabled == 'yes' ) {
            $message = __( 'Startrack Shipping Method is enabled, but the origin postcode has not been set.', 'wootan' );
            // $this->errors['postcode_not_set'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        } 

        elseif ( !is_dir( $this->s_path ) and $this->enabled == 'yes' ){
            $message = __( 'Startrack Shipping Method is enabled, but the secure_path is not a valid directory', 'wootan' );
            // $this->errors['invalid_secure_path'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        }

        elseif ( !is_readable( $this->s_path ) and $this->enabled == 'yes' ){
            __( 'Startrack Shipping Method is enabled, but the secure_path is not a readable directory', 'wootan' ) ;
            // $this->errors['secure_path_not_readable'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        }

        elseif ( !file_exists( $this->connection['wsdlFilespec'] ) and $this->enabled == 'yes' ){
            $message = __( 'Startrack Shipping Method is enabled, but the plugin does not have read access to the wsdl file', 'wootan' ) ;
            // $this->errors['invalid_wsdl_file'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        }

        elseif ( !$this->is_connected() and $this->enabled == 'yes' ){
            $message = __( 'Startrack Shipping Method is enabled, but the plugin could not connect to the StarTrack API. Check your settings and try again.', 'wootan' );
            // $this->errors['not_connected'] = $message;
            echo $this->get_admin_notice( $message );
            $this->bad_environment = true;
        }
    }

    function validate_secure_path_field( $key ) {
        $secure_path = get_option('wsdl_file', "NOTSET");
        if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) )
            $secure_path = trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
        if( !is_dir( $this->s_path) ){
            //$this->errors['secure_path'] = __('This is not a valid directory', 'wootrack');
        } else if( !is_readable( $this->s_path) ) {
            //$this->errors['secure_path'] = __('PHP does not have read access to this directory', 'wootrack');
        } /*else if( !is_writeable($s_path) ) {
            $this->errors['secure_path'] = __('PHP does not have write access to this directory', 'wootrack');
        }*/ else {
            //$this->validations['secure_path'] = __('Directory is accessible', 'wootrack');
        }
        return $secure_path;
    }
    
    /**function validate_wsdl_file_field( $key ){
        $wsdl_file = get_option('wsdl_file', "NOTSET");
        if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) )
            $wsdl_file = trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
        if( !is_readable( $wsdl_file) ) {
            $this->errors['wsdl_file'] = __('PHP does not have read access to this file', 'wootrack');
        } else {
            //$this->validations['wsdl_file'] = __('File is accessible', 'wootrack');;
        }
        return $wsdl_file;
    }**/
    
    function get_location( $pcode ){
        $_procedure = "GET_LOCATION";

        $location = array();
        
        if($this->is_connected()){
            $request = array(
                'parameters' => array(
                    'header'  => $this->header,
                    'address' => array(
                        'postCode' => $pcode
                    )
                )
            );

            try {
                $response = $this->invokeWebService('validateAddress', $request);
            }
            catch (SoapFault $e) {
                if(WOOTRACK_DEBUG) error_log($_procedure."could not connect to starTrack eServices: ".$e);
                $response = false;
            }      
            
            if( $response && $response->matchedAddress ){
                $location['postCode'] = $pcode;
                $location['suburb']   = $response->matchedAddress[0]->suburbOrLocation;
                $location['state']    = $response->matchedAddress[0]->state;
            } else {
                //TODO: add admin message: Could not match postcode
                return false;
            }
        }

        return $location;
    }    
    
    function validate_sender_pcode_field( $key ) {
        $pcode = "";
        if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) )
            $pcode = wp_kses_post( trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) );
        
        //assert pcode is a pcode
        
        $location = $this->get_location( $pcode );
        if($location){
            $this->sanitized_fields['sender_suburb'] =  $location['suburb'];
            $this->sanitized_fields['sender_state']  =  $location['state'];
        } else {
            //$this->errors['sender_pcode'] = __('Could not match postcode','wootrack');
            $this->sanitized_fields['sender_suburb'] =  '';
            $this->sanitized_fields['sender_state']  =  '';            
        }
        
        return $pcode;
    }
    
    //function validate_startrack_connection() {
        
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
    
    function calculateShippingParams($contents){
        //default values for parameters
        $params = array(
            'weight'    => 0,
            'volume'    => 0,
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
        
        If(WOOTRACK_DEBUG) error_log( "Shipping params: \n".serialize($params) );
        
        return $params;
    }
        

    
    
    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    function calculate_shipping( $package ) {

        If(WOOTRACK_DEBUG) error_log("-> destination: \n    " . serialize($package['destination']));
        
        $destination = $package['destination'];
        
        if($destination['country'] == 'AU'){    
            // Validate receiver location
            $receiverLocation = $this->get_location( $destination['postcode'] );
            if( !$receiverLocation ){
                //TODO: add admin message: Could not match postcode
                return;
            }
            
            //If(WOOTRACK_DEBUG) error_log( "Validating receiver location: \n".serialize($response) ); 
            
            $prefs = get_option($this->service_pref_option, false);
            $params = $this->calculateShippingParams($package['contents']);

            foreach($prefs as $code => $name) {                                
                if($this->is_connected()){
                    $request = array(
                        'parameters' => array(
                            'header'            => $this->header,
                            'senderLocation'    => $this->sender_location,
                            'receiverLocation'  => $receiverLocation,
                            'serviceCode'       => $code,
                            'noOfItems'         => $params['noOfItems'], 
                            'weight'            => $params['weight'   ],
                            'volume'            => $params['volume'   ],
                        )
                    );

                    try {
                        $response = $this->invokeWebService('calculateCost', $request);
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
                        If(WOOTRACK_DEBUG) error_log( "Exception in calculateCost, " . $e );
                        If(WOOTRACK_DEBUG) error_log( "details: " . $e->getMessage() );
                        //TODO: add admin message: could not contact StarTrack eServices.
                    }
                    
                }
                
                If(WOOTRACK_DEBUG) error_log( "response: \n".serialize($response) );
                // If(WOOTRACK_DEBUG) error_log( 'request: '.serialize($request).'\n response: '.serialize($response) );
            }
        }
    }
}

?>