<?php
/*
   Plugin Name: WooTrack
   Plugin URI: http://wordpress.org/extend/plugins/wootrack/
   Version: 0.1
   Author: <a href='http://dev.laserphile.com'>Derwent McElhinney</a>
   Description: Registers the StarTrack shipping method in WooCommerce
   Text Domain: wootrack
   License: GPLv3
  */

/*
    "WordPress Plugin Template" Copyright (C) 2013 Michael Simpson  (email : michael.d.simpson@gmail.com)

    This following part of this file is part of WordPress Plugin Template for WordPress.

    WordPress Plugin Template is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    WordPress Plugin Template is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Contact Form to Database Extension.
    If not, see http://www.gnu.org/licenses/gpl-3.0.html
*/

include_once('eServices/eServices.php');
include_once('eServices/CustomerConnect.php');

$Wootrack_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function Wootrack_noticePhpVersionWrong() {
    global $Wootrack_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "WooTrack" requires a newer version of PHP to be running.',  'wootrack').
            '<br/>' . __('Minimal version of PHP required: ', 'wootrack') . '<strong>' . $Wootrack_minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'wootrack') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function Wootrack_PhpVersionCheck() {
    global $Wootrack_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $Wootrack_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'Wootrack_noticePhpVersionWrong');
        return false;
    }
    return true;
}

function Wootrack_noticeWoocommerceNotInstalled() {
    echo 
        '<div class="updated fade">' .
        __('Error: plugin "WooTrack" requires WooCommerce to be installed',  'wootrack') .
        '</div>';
}

function Wootrack_WoocommerceCheck() {
    if( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        add_action('admin_notices', 'Wootrack_noticeWoocommerceNotInstalled');
        return false;
    }
    return true;
}

/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function Wootrack_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('wootrack', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// First initialize i18n
Wootrack_i18n_init();


// Next, run the version check.
// If it is successful, continue with initialization for this plugin
if (Wootrack_PhpVersionCheck() and Wootrack_WoocommerceCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    // include("Wootrack_Register_Shipping.php");
    include_once('wootrack_init.php');
    Wootrack_init(__FILE__);
}
