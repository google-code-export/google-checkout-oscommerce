GOOGLE CHECKOUT MODULE FOR OSCOMMERCE v1.3 RC1 - 04/10/2007

INTRODUCTION
============
The Google Checkout module for osCommerce adds Google Checkout as a Checkout 
 Module within osCommerce.
This will allow merchants using osCommerce to offer Google Checkout as an 
 alternate checkout method.
The plugin provides Level 2 integration of Google Checkout with osCommerce.

Plugin features include:
1. Posting shopping carts to Google Checkout
2. Shipping support, now you can use Merchant Calculated Shipping Rates!
   - Tested to work with FedEx and UPS XML.
3. Tax support
4. User and order updates within osCommerce
5. Order processing using osCommerce Admin UI 

For continued support, you may use either support area:

http://forums.oscommerce.com/index.php?showtopic=229637

REQUIREMENTS
============
osCommerce v2.2
PHP3 or PHP4 or PHP5 with cURL(libcurl) installed and enabled


INSTALLATION NOTES
==================
* Follow instructions contained in the INSTALLATION file.
* register_globals can be either On or Off. 
Verify the installation from the Admin site and selecting MODULES->PAYMENTS and 
checking if Google Checkout is listed as a payment option.


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
    More info:
    http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html

10. Google Analytics Id: Add google analytics to your e-commerce. Now there is a 
   feature in GA to integrate easily with any e-commerce with GoogleCheckout.
   More info: See below "Enabling E-Commerce Reporting for Google Analytics".
   
11. Continue shopping URL: The URL customers will be redirected to if they 
   follow the link back to your site after checkout.

Your Google Checkout setup page is correct if, upon viewing it, a non-disabled 
Google Checkout button appears.

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
This Value will be used if for any reason Google Checkout cannot reach your API 
callback to calculate the shipping price. 

The available shipping methods for each shipping provider must be configured in 
catalog/includes/modules/payment/googlecheckout.php in the mc_shipping_methods 
parameter. If you want to disable one or more methods, just comment them out.
Be aware that if you mix flat rate and real time rates, both will be taken as 
merchant-calculated-shipping. 

Script to create new shipping methods
http://demo.globant.com/~brovagnati/tools -> Shipping Method Generator



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

All request and response messages will be logged to the file 
catalog/googlecheckout/response_message.log.
In case an order payment is declined or an order is canceled 
(by Google or otherwise), you can add code to 
catalog/googlecheckout/responsehandler.php for any specific actions you 
need to take.


PROJECT HOME
============
To check out the latest release or to report issues, go to 
http://code.google.com/p/google-checkout-oscommerce


GROUP DISCUSSIONS
=================
To meet other developers and merchants who have integrated Google Checkout with 
osCommerce and to discuss any topics related to integration of Google Checkout 
with osCommerce, go to http://forums.oscommerce.com/index.php?showtopic=229637

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
MOST COMMON MISTAKES
====================
1. Make sure you set the file attribute to 777 for /googlecheckout/response_error.log 
   and /googlecheckout/response_message.log files.
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
1. Problem: /public_html/googlecheckout/response_message.log) [function.fopen]: 
    failed to open stream: Permission denied.
   Solution: Set the file attribute to 777 for /googlecheckout/response_error.log 
    and /googlecheckout/response_message.log files.
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
7. Problem: (/public_html/googlecheckout/response_error.log) 
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
    
KNOWN BUGS - (Report bugs at http://forums.oscommerce.com/index.php?showtopic=229637)
==========


CHANGE LOG
==========
09/26/2006 v1.0.0 (Google Checkout team)
           - Initial release. 

09/27/2006 v1.0.1 (Google Checkout team)
           - Updated the original module to run on either PHP4 or PHP5.

10/05/2006 v1.0.1b (Google Checkout team)
           - Step-by-step installation instructions included.

10/18/2006 v1.0.2 (Google Checkout team)
           - Fixed minor bugs in responsehandler.php and orders.php files
  
11/17/2006 v1.0.3 (ropu)
           - Add support for Merchant Calculated Shipping Rates.
           - Fixed minor bugs in responsehandler.php and orders.php files
           - Change the XML parser and builder
           - Removed getallheader() function
           - Fixed wrong Qty in Admin UI
           - Fixed modules not saving their settings
           - Fixed Notify Customer option

12/04/2006 v1.0.4 (ropu)
           - Add order-state-change, risk-information and charge-amount notification into the Admin UI
           - Fix Shopping cart not obtained from session. See TROUBLE SHOOTING.

01/10/2007 v1.1.0b3 (rszrama)
           - Bugfix compilation so people stop downloading the old code till 1.1.0 comes out!
           - Read v1-1-0b2.txt for more information.
           
01/12/2007 v1.1.0b4 (ropu)
           - Fix <tax-table-selector> strict validation.
           
02/26/2007 v1.2 (ropu)
           - Add multisocket feature for merchant-calculations (alfa)(optional)
           - Different algorithm to retrieve quotes
           - Add Google Analytics Support
           - Add support for PHP CGI installations
           - Add in UPSXML methods by default
           - Add user and password for Google Checkout buyers
           - Items retrieved from Merchant-private-item-data instead of session.
           - Many bug fixes

03/05/2007 v1.2 RC3 (ropu)
   - Fix gray button when Tax Class selected bug (Thx BlessIsaacola)
   
04/10/2007 v1.3RC1 (ropu)
   - Add tracking data to the Admin UI Orders
   - Fixed SSL issue with Google Analytics feature
   - International Shipping Features
   - Restricting Shipping Options for Post Office (P.O.) Box Addresses feature
   - International Tax Features
   - Selecting a Rounding Policy for Tax Calculations
   - Fixed Tax for zones
   - Fixed Tax for products
   - Added support for All Areas Zones
   - Add a configuration to disable Google Checkout Button when are virtual good in the cart
   		(double check http://checkout.google.com/seller/policies.html#4)
   - Disable multisocket Option :(