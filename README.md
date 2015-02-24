wootrack
========

A wordpress plugin that registers the StarTrack shipping method in WooCommerce.

This is an advanced plugin that is difficult to configure and not written to be very robust. Use at your own risk :P

Installation
------------

Because of the way the StarTrack API handles SOAP calls, certail files (a certificate and xml files) need to be remotely accessible on your server. In it's current state, this plugin requires root server privileges to function (sorry). 

*Step 1:* Download this repository as a zip archive and install and activate the plugin through the wordpress plugin installer

*Step 2:* Using a shell on the server or a file browser, copy the contents of the "secure" directory to a "cgi-bin" directory on the web root of your server

*Setp 3:* Open worpress admin -> woocommerce -> settings -> shipping. If you've correctly activated the plugin you should see the StarTrack method. Open this shipping method.

*Step 4:* Configure the plugin with the username and API keys supplied by startrack. Use the full file path of the secure directory, eg: "/home/website/public_html/cgi-bin/". Use the the staging xml file (eServicesStagingWSDL.xml) to start with, defaults should be fine. If you've entered your password correctly, the 'eServices API connection status' should be 'N'

Issues
------

this plugin has been working fine for almost a year however as of a week ago, some changes to the startrack server have caused it to stop working, I'm currently workin on a solution with StarTrack.
