GOOGLE CHECKOUT MODULE FOR OSCOMMERCE v1.4Alpha1 - 06/20/2007

INTRODUCTION
============
The Google Checkout module for osCommerce adds Google Checkout as a Checkout 
 Module within osCommerce.
This will allow merchants using osCommerce to offer Google Checkout as an 
 alternate checkout method.
The plugin provides Level 2 integration of Google Checkout with osCommerce.

Plugin features include:
1. Posting shopping carts to Google Checkout
2. Shipping support with Merchant Calculated Shipping Rates!
3. Tax support
4. User and order updates within OSC
5. Order processing using OSC Admin UI 
6. Order Totals Support

For continued support, you may use either support area:

http://groups.google.com/group/google-checkout-for-osc-mod-support

REQUIREMENTS
============
osCommerce v2.2
PHP3 or PHP4 or PHP5 with cURL(libcurl) installed and enabled


INSTALLATION NOTES
==================
1. Follow instructions contained in the INSTALLATION file.
2. Verify the installation from the Admin site and selecting MODULES->PAYMENTS 
    and checking if Google Checkout is listed as a payment option.
3. Set the file attribute to 777 for /googlecheckout/logs/response_error.log and 
    /googlecheckout/logs/response_message.log files.
4. Go to http://<url-site-url>/googlecheckout/responsehandler.php
    If you get a 'Invalid or not supported Message', go to the next section.  
    If you get any errors,  you must correct all errors before proceeding.  
    Refer to the troubleshooting section below or go to the support forum for help.


SETUP ON ADMIN UI
=================
Select and install the Google Checkout payment module. The following are some 
of the fields you can update:

1. Enable/Disable: Enable this to use Google Checkout for your site.
2. .htaccess Basic Authentication Mode with PHP over CGI? If your site is 
   installed on a PHP CGI you must disable Basic Authentication over PHP. 
   To avoid spoofed messages (only if this feature is enabled) reaching 
   responsehandler.php, set the .htaccess file with the script linked 
   (http://your-site/admin/htaccess.php). 
   Set permission 777 for http://your-site/googlecheckout/ before running 
   the script. Remember to turn back permissions after creating the files.
3. Merchant ID and Merchant Key:(Mandatory) If any of these are not set and the 
   module is enabled, a disabled (gray) Checkout button appears on the Checkout 
   page. Set these values from your seller Google account under the 
   Settings->Integration tab.
4. Operation Mode: Test your site with Google Checkout's sandbox server before 
   migrating to production. You will need it signup for a separate Google Checkout 
   sandbox account at http://sandbox.google.com/sell. Your sandbox account will 
   have a different Merchant ID and Merchant Key. When you are ready to run 
   against the production server, remember to update your merchant ID and key 
   when migrating.
5. Merchant Calculation Mode of Operation: Sets Merchant calculation URL for 
   Sandbox environment. Could be HTTP or HTTPS. (Checkout production environment 
   always requires HTTPS.)
6. Disable Google Checkout for Virtual Goods?: This configuration is enabled and
    there is any virtual good in the cart the Google Checkout button will be
    shown disabled.
    (double check http://checkout.google.com/seller/policies.html#4)
    
7. Allow US PO BOX shipping. Setted to false, you won't ship to any PO address
    in the US.

8. Default Values for Real Time Shipping Rates: Set your default values for 
   all merchant calculated shipping rates. This values will be used if for any 
   reason Google Checkout cannot reach your API callback to calculate the 
   shipping price.

9. Rounding Policy Mode and Rounding Policy Rule: Determines how Google Checkout
    will do rounding in prices.
    US default: rounding rule TOTAL, rounding mode is HALF_EVEN
    UK default: rounding rule PER_LINE, rounding mode is HALF_UP
    
    More info:
    http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html

10. Also send Notification with OSC, if enabled, will send an email using the 
     OSC internal email system if the comment in an order is larger than 254 
     chars, limit for a google send message. If this happens a warning will be 
     shown in the Admin UI.

11. Google Analytics Id: Add google analytics to your e-commerce. Now there is a 
   feature in GA to integrate easily with any e-commerce with GoogleCheckout.
   More info: See below "Enabling E-Commerce Reporting for Google Analytics".
   
12. 3rd Party Tracking: Do you want to integrate the module 3rd party tracking? 
			Add the tracker URL, NONE to disable.
		More info:
		http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html
   
13. Continue shopping URL: The URL customers will be redirected to if they 
   follow the link back to your site after checkout.
   (http://your-site/OSC_dis/<input data>)
   Note:Use gc_return.php for special page that will show all the purchased items 
   with Google Checkout

Your Google Checkout setup page is correct if, upon viewing it, a non-disabled 
Google Checkout button appears. Double check the INSTALLATION file for more info

Enabling E-Commerce Reporting for Google Analytics
==================================================
To track Google Checkout orders, you must enable e-commerce reporting for your 
website in your Google Analytics account. The following steps explain how you 
enable e-commerce reporting for your website:

   1. Log in to your Google Analytics account.
   2. Click the Edit link next to the profile you want to enable. This link 
      appears in the Settings column.
   3. On the Profile Settings page, click the Edit link in the Main Website 
      Profile Information box.
   4. Change the selected E-Commerce Website radio button from No to Yes.
More info: 
http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html

MERCHANT CALCULATED SHIPPING
============================
In order to use this module you must have some Real Time Shipping provider,
 such as USPS or FedEx. This Module must be activated and configured in
 Modules->Shipping. For each enabled module you'll have to set the default
 values in the Google Checkout Admin UI.
This Value will be used if for any reason Google Checkout cannot reach your
 API callback to calculate the shipping price. 

The available shipping methods for each shipping provider must be configured
 in includes/modules/payment/googlecheckout.php in the mc_shipping_methods
 parameter. If you want to disable one or more methods, just comment them out.
Be aware that if you mix flat rate and real time rates, both will be taken
 as merchant-calculated-shipping. 

Script to create new shipping methods
	http://your-site/osccart_dir/googlecheckout/shipping_generator
More Info: 
	http://your-site/osccart_dir/googlecheckout/shipping_generator/README


TRACKING USERS AND ORDERS
=========================
In order to provide this support (as required for Level 2 integration), update 
the API callback request field in the seller account to 
https://<url-site-url>/catalog/googlecheckout/responsehandler.php . Note that 
the production Checkout server requires an SSL callback site.
The Sandbox server accepts non SSL callback URLs, 
http://<url-site-url>/catalog/googlecheckout/responsehandler.php

View your Google Checkout customers and their orders in the Reports tab. 
For each order, the default starting state is PENDING. 
The state changes to PROCESSING and DELIVERED as follows; corresponding messages
will be sent out to the Checkout server for order processing.

| Original State | New State  | Action                         |  Customer Notification    |
| PENDING        | PROCESSING | charging the order             |  Processing order message |
| PROCESSING     | DELIVERED  | marking the order for delivery |  Shipped order message    |

You can add a Tracking Number in the state change from PROCESSING to DELIVERED. 
A text field and a combo with the shipping providers will be show when the order
is in the PROCESSING state.

Any comments added during state change will be sent to the buyer account page if
you have selected the Notify Customer option.

All statechanges are added as notes in the Admin UI
All request and response messages will be logged to the file 
catalog/googlecheckout/logs/response_message.log.

In case an order payment is declined or an order is cancelled(by Google or
 otherwise), you can add code to googlecheckout/responsehandler.php for 
 any specific actions you need to take.


PROJECT HOME
============
To check out the latest release or to report issues, go to 
http://code.google.com/p/google-checkout-oscommerce


GROUP DISCUSSIONS
=================
To meet other developers and merchants who have integrated Google Checkout with 
osCommerce and to discuss any topics related to integration of Google Checkout 
with osCommerce, go to http://groups.google.com/group/google-checkout-for-osc-mod-support

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
MOST COMMON MISTAKES
====================
1. Make sure you set the file attribute to 777 for 
    /googlecheckout/logs/response_error.log and /googlecheckout/logs/response_message.log files.
2. Set your Google callback url to https://<url-site-url>/googlecheckout/responsehandler.php
   In Sandbox, HTTPS is not required.
   In Production mode, HTTPS is required.
   Set the correct option in the Google Checkout Admin UI
   Links for supported SSL certificates:
    http://www.google.com/checkout/ssl-certificates
    http://checkout.google.com/support/sell/bin/answer.py?answer=57856
3. Make sure you are using the correct combination of Merchant ID and 
   Merchant Key. Remember that Sandbox and Production Mode have different ones.
  

TROUBLE SHOOTING
================
1. Problem: /public_html/googlecheckout/logs/response_message.log) [function.fopen]: 
    failed to open stream: Permission denied.
   Solution: Set the file attribute to 777 for /googlecheckout/logs/response_error.log 
    and /googlecheckout/logs/response_message.log files.
2. Problem: Test order shows up in Google but not admin.
   Solution: There is an error somewhere in the file 
    /googlecheckout/responsehandler.php or you have set a wrong API callback 
    function in your seller Google account under the Settings->Integration tab.
3. Problem: <error-message>Malformed URL component: expected id: 
    (\d{10})|(\d{15}), but got 8***********4 </error-message>
   Solution: You have an extra space after your Google merchant id. 
    Go to Admin->payment.  Edit Googlecheckout module.  
    Extra space will disappear. Click update button.
4. Problem: <error-message>No seller found with id 7************8</error-message>
   Solution: Wrong merchant id.  Sandbox merchant id can only be use with 
    sandbox accounts.  Sandbox and Live mode use different merchant id. 
5. Problem: sun.security.validator.ValidatorException: PKIX path building 
    failed: sun.security.provider.certpath.SunCertPathBuilderE xception: unable 
    to find valid certification path to requested target
   Solution: Your SSL certificate is not accepted by Google Checkout.
   Links for supported SSL certificates:
    http://www.google.com/checkout/ssl-certificates
    http://checkout.google.com/support/sell/bin/answer.py?answer=57856
6. Problem: <error-message>Bad Signature on Cart</error-message>
   Solution: Incorrect Merchant key.
7. Problem: (/public_html/googlecheckout/logs/response_error.log) 
    Tue Nov 28 8:56:21 PST 2006:- Shopping cart not obtained from session. 
   Solution: Set to False admin->configuration->session->Prevent Spider Sessions
    configuration (Thx dawnmariegifts, beta tester)
   Side effects: You'll see spiders as active users.
   Solution 2 (Recommended): Remove any string like 'jakarta' in the includes/spider.txt
8. Problem:
    Warning: main(admin/includes/configure.php) [function.main]: failed to open 
     stream: No such file or directory in /public_html/googlecheckout/gcheckout.php
     on line 34
		Fatal error: main() [function.require]: Failed opening required 
     'admin/includes/configure.php' (include_path='.:/usr/lib/php:/usr/local/lib/php')
     in /public_html/googlecheckout/gcheckout.php on line 33
   Solution:
			Change googlecheckout/gcheckout.php Line 34 'admin' for the modified admin
			 directory
      require_once('admin/includes/configure.php');      
10. IIS Note::  For HTTP Authentication to work with IIS, 
		the PHP directive cgi.rfc2616_headers must be set to 0 (the default value).
		Will use the $_SERVER['HTTP_AUTHORIZATION'] header
    
KNOWN BUGS -
==========
(Report bugs at 
 http://groups.google.com/group/google-checkout-for-osc-mod-support)


CHANGE LOG
=========
See CHANGELOG file. 	 	