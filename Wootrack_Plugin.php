<?php


include_once('Wootrack_LifeCycle.php');
include_once('eServices/eServices.php');

class Wootrack_Plugin extends Wootrack_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'Activated' => array(__('Activate StarTrack shipping methods', 'wootrack')),
            'AccountNo' => array(__('StarTrack Account Number', 'wootrack')), 
            'AccessKey' => array(__('StarTrack Access Key', 'wootrack')), 
            'Username'  => array(__('StarTrack Username', 'wootrack')),
            'Password'  => array(__('StarTrack Password', 'wootrack')),
            'wsdlFile'  => array(__('WSDL File Spec', 'wootrack')),
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr) > 1) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'WooTrack';
    }

    protected function getMainPluginFileName() {
        return 'wootrack.php';
    }
    
    protected function getTableMeta() {
        return array(
            'servicepreferences' => array(
                'service_code' => array(
                    'name'  => 'Service Code',
                    'sql'   => 'INT NOT NULL AUTO_INCREMENT',
                    'primary' => True    
                ),
                'service_name' => array (
                    'name'  => 'Service Name',
                    'sql'   => 'VARCHAR(50)',
                ),
                'enabled' => array (
                    'name' => 'Enabled',
                    'sql'  => 'BOOL'
                ),
                'price_adjustment' => array(
                    'name'  => 'Price Adjustment',
                    'sql'   => 'FLOAT'
                )
            )
        );
    }
    
    protected function getWPTableNames() {
        $meta = getTableMetadata();
        return array_map( $this->prefixTableName, $meta->keys() );
    }
            

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
        
        global $wpdb;
        foreach( $this->getTableMeta() as $tableName => $columns ){
                $sql = 
                    "CREATE TABLE IF NOT EXISTS " . 
                    $this->prefixTableName($tableName) .
                    " ( ";
                    
                foreach( $columns as $columnName => $columnMeta ){
                    $sql .= "'$columnName' " . $columnMeta['sql'] . " ";
                    if( $columnMeta['primary'] ) $sql .= "PRIMARY KEY( $columnName )";
                }
                
                $sql .= " ); ";
                $wpdb->query( $sql );
        };
        //TODO: Assert tables exist
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
        
        global $wpdb;
        $wpdb->query(
            "DROP TABLE IF EXISTS " . Join(", ", $this->getWPTableNames()) 
        );
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }
    
    public function activate() {
        
        include('Wootrack_Register_Shipping.php');
        
        // // prepare connection details
        
        
        // // $connection = array(
            // // 'username'      => $this->getOption('Username'),
            // // 'password'      => $this->getOption('Password'),
            // // 'userAccessKey' => $this->getOption('AccessKey'),
            // // 'wsdlFilespec'  => $this->getOption('wsdlFile')
        // // );
        
        // // prepare request
        // $request = array(
            // // 'code'          =>  
            // // 'lastUpdated'   =>
        // );
        
        // // get shipping methods from StarTrack
        // $eService = new STEeService();
        
        // $response = $eService->invokeWebService($connection, 'getService', $request);
        
        // //echo serialize($response);
        // //create shipping classes
        
        // //register shipping classes
    
        // // woocommerce_register_shipping_method();
    }
 
    public function deactivate() {
        //deregister shipping classes
        
    }
    
    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));


        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }
    
    


}
