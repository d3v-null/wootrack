wootrack
========

A wordpress plugin that registers the StarTrack shipping method in WooCommerce.

This is an advanced plugin that is difficult to configure and not written to be very robust. Use at your own risk :P

Installation
------------

Because of the way the StarTrack API handles SOAP calls, certail files (a certificate and xml files) need to be remotely accessible on your server. In it's current state, this plugin requires ftp access (sorry). 

*Step 1:* Download this repository as a zip archive and install and activate the plugin through the wordpress plugin installer

*Step 2:* Using a shell on the server or a file browser, copy the contents of the "secure" directory to a "cgi-bin" directory on the web root of your server

*Setp 3:* Open worpress admin -> woocommerce -> settings -> shipping. If you've correctly activated the plugin you should see the StarTrack method. Open this shipping method.

*Step 4:* Enter the location on the server of the "secure" directory eg: "/home/ypu-rwebsite/public_html/cgi-bin/" (remember to terminate with a /)

*Step 5:* Enter the WSDL File. Use the the staging xml file (eServicesStagingWSDL.xml) to start with, then when your connection works change this to eServicesProductionWSDL.xml.

*Step 6:* Configure the plugin with the username and API keys supplied by startrack. 

*Step 7:* Fill in and save the rest of the settings and ensure that there are no warnings before continuing

*Step 8:* When you have a connection to the StarTrack eServices API, you will be able to select from the available shipping options which methods to give to your customers.

Issues
------

this plugin has been working fine for over a year, however as of a week ago, there has been a problem connecting to the eServices API with the Production XML file, which I am in the process of resolving with StarTrack. Until then, only the Stagin XML will work (this is actually sufficient for the plugin to run however)

Debugging
---------

Debugging of this plugin can be enabled by setting the WOOTRACK_DEBUG constant in wp-config.php. 

    define('WOOTRACK_DEBUG', true);

plugin notices will be written to wp-content/debug.log and an extra setting validation screen will be seen in the admin page.

Support
-------

If you have any problems using or installing this plugin, please submit a github issue or contact me on derwent@laserphile.com