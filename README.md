wootrack
========

A wordpress plugin that registers the StarTrack shipping method in WooCommerce.

This is an advanced plugin that is difficult to configure. It will not work out of the box, and you will need to obtain an API key from StarTrack. 

Installation
------------

= Requirements =

You will need a copy of the StarTrack eServices SDK (This plugin was developed using version 4.5a) and an API key for this plugin to function. If you are a StarTrack customer, they will send you a copy on request.

Because of the way the StarTrack API handles SOAP calls, certain files (a certificate and xml files) need to be remotely accessible on your server. In it's current state, this plugin requires ftp access to your webserver. 

Once you have satisfied these requirements you can install the plugin

= Instructions =

*Step 1:* Download this repository as a zip archive and install and activate the plugin through the wordpress plugin installer

*Step 2:* Using a shell on the server or an ftp file browser, copy the contents of the "secure" directory to a "cgi-bin" directory on the web root of your server

*Setp 3:* Open worpress admin -> woocommerce -> settings -> shipping. If you've correctly activated the plugin you should see the StarTrack method. Open this shipping method.

*Step 4:* Enter the location on the server of the "secure" directory eg: "/home/ypu-rwebsite/public_html/cgi-bin/" (remember to terminate with a /)

*Step 5:* Enter the WSDL File. Use the the staging xml file (eServicesStagingWSDL.xml) to start with, then when your connection works change this to eServicesProductionWSDL.xml.

*Step 6:* Configure the plugin with the username and API keys supplied by startrack. 

*Step 7:* Fill in and save the rest of the settings and ensure that there are no warnings before continuing

*Step 8:* When you have a connection to the StarTrack eServices API, you will be able to select from the available shipping options which methods to give to your customers.

Frequently Asked Questions
--------------------------

= Why isn't my plugin working? =

First you will need to make sure that you have access to the eServices API. Use the developer notes instructions in the SDK documentation under General/Readme.pdf

Issues
------
= API issues =
This plugin functioned very well from 2014 to 2015, accurately and reliably giving shipping estimates to wordpress in good time, however as of mid 2015, Startrack's API hasn't been doing so well. Without warning, Startrack can and has made updates to the production version of the API that have broken the plugin, with each problem taking months for support to patch. We have also had numerous problems with service disruptions when the Startrack servers are under load, where the api would take half a second to respond to calls. Sometimes XML files between Staging and Production will resolve issues temporarily while Startrack get their act together since staging and production both use different versions of the API (I KNOW RIGHT?). Also note that there is less authenticating calls when using staging and the server is less contended. Conveniently this plugin only uses the getServiceCodes and calculateCost calls so the staging XML is sufficient for the plugin to function fully.

= Volume Bug =
For some reason if the volume of an object is too small, the plugin won't be able to calcualte the cost. Any volume below 10^-6 m^3 will cause this error.

Debugging
---------

Debugging of this plugin can be enabled by setting the WOOTRACK_DEBUG constant in wp-config.php. 

    define('WOOTRACK_DEBUG', true);

plugin notices will be written to wp-content/debug.log and an extra setting validation screen will be seen in the admin page.

Support
-------

If you have any problems using or installing this plugin, please submit a github issue or contact me on derwent@laserphile.com

If this plugin has saved you a bunch of money, why not by me a [beer](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=2PF5FGAHHBFU2&lc=AU&item_name=Laserphile%20Developers&currency_code=AUD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted) ? :D
