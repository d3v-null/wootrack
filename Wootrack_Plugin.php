<?php

include_once('Wootrack_LifeCycle.php');

class Wootrack_Plugin extends Wootrack_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    // public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
  
        // );
    // }

   // protected function getOptionValueI18nString($optionValue) {
       // $i18nValue = parent::getOptionValueI18nString($optionValue);
       // return $i18nValue;
   // }

    // protected function initOptions() {
        // $options = $this->getOptionMetaData();
        // if (!empty($options)) {
            // foreach ($options as $key => $arr) {
                // if (is_array($arr) && count($arr) > 1) {
                    // $this->addOption($key, $arr[1]);
                // }
            // }
        // }
    // }

    public function getPluginDisplayName() {
        return 'WooTrack';
    }

    protected function getMainPluginFileName() {
        return 'wootrack.php';
    }
    
    public function getTableMeta() {
        return array(
            'service_preferences' => array(
                'columns' => array(
                    'code'  => array(
                        'disp'  => true,
                        'name'  => 'Service Code',
                        'sql'   => 'CHAR(3) NOT NULL',
                    ),
                    'name'  => array (
                        'disp'  => true,
                        'name'  => 'Service Name',
                        'sql'   => 'VARCHAR(50)',
                    ),
                    // 'price_adj'     => array(
                        // 'disp'  => true,
                        // 'name'  => 'Price Adjustment',
                        // 'sql'   => 'FLOAT'
                    // ),
                ),
                'primary' => array(
                    'code'
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
        
        If(WP_DEBUG) error_log("installing database tables");
        
        global $wpdb;
        foreach( $this->getTableMeta() as $tableName => $tableMeta ){
                $sql = 
                    "CREATE TABLE IF NOT EXISTS\n" . 
                    $this->prefixTableName($tableName) .
                    "(\n";
                    
                foreach( $tableMeta['columns'] as $columnName => $columnMeta ){
                    $sql .= "    ".$columnName." ".$columnMeta['sql'].",\n";
                    //if( $columnMeta['primary'] ) $sql .= "PRIMARY KEY( $columnName )";
                }
                
                $sql .= "    PRIMARY KEY( ".implode(', ', $tableMeta['primary'])." )\n";
                
                $sql .= ")";
                
                If(WP_DEBUG) error_log( "creating table with SQL: \n". $sql );
                
                $wpdb->query( $sql );
        };
        //TODO: Assert tables exist
        
        //populate service preferences
        
        
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
        
        If(WP_DEBUG) error_log("installing database tables");
        
        global $wpdb;
        $wpdb->query(
            "DROP TABLE IF EXISTS " . Join(", ", $this->getWPTableNames()) 
        );
    }
    
    
    /**
     * Fetches 
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function getTable($tableName){
        if(in_array($tableName, array_keys($this->getTableMeta()))){
            global $wpdb;
            return $wpdb->get_results(
                "SELECT * FROM ".$this->prefixTableName($tableName)
            );
        }
        //TODO: throw exception instead
        else return array();
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }
    
    public function activate() {
        
        If(WP_DEBUG) error_log("activating plugin");
        

    }
 
    public function deactivate() {
        //deregister shipping classes
        If(WP_DEBUG) error_log("deactivating plugin");
    }
    
    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        // add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

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
