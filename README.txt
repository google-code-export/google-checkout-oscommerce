GOOGLE CHECKOUT MODULE FOR OSCOMMERCE v1.5.0

NOTE: This module will only work with osCommerce v2.2 RC 2a:

  http://www.oscommerce.com/solutions/downloads


INTRODUCTION
============

This module adds Google Checkout as a payment module in osCommerce. This allows 
merchants using osCommerce to offer Google Checkout as a payment method.
The module provides Level 2 integration of Google Checkout with osCommerce.

Features include:
1. Posting shopping carts to Google Checkout
2. Shipping support (include merchant-calculated shipping)
3. Tax support
4. User and order updates within OSC
5. Order processing using OSC Admin UI
6. Order totals support

For support, please visit the forum:

http://groups.google.com/group/google-checkout-for-osc-mod-support


REQUIREMENTS
============

osCommerce v2.2 RC 2a
PHP3, PHP4 or PHP5 with cURL(libcurl) installed and enabled


INSTALLATION NOTES
==================

1. Follow instructions contained in INSTALLATION.txt.

2. Verify that installation succeeded from the osCommerce admin page by 
   selecting MODULES->PAYMENTS and checking if Google Checkout is listed.

3. Point your browser to 

      http://<your-domain>/catalog/googlecheckout/responsehandler.php

   If you get a 'Invalid or not supported Message', go to the next section.  
   If you get any errors,  you must correct all errors before proceeding.  
   Refer to the troubleshooting section below or go to the support forum for 
   help.


CONFIGURATION
=============

In the OSC admin UI, select and install the Google Checkout payment module. The 
following are some of the fields you can update:

0. Installed GC module version

1. Enable/Disable: Enable this to use Google Checkout for your site.

2. Operation Mode: Test your site with Google Checkout's sandbox server before 
   migrating to production. You will need to signup for a separate 
   Google Checkout Sandbox account at http://sandbox.google.com/checkout/sell. 
   Your sandbox account will have a different Merchant ID and Merchant Key. 
   When you are ready to run against the production server, remember to update 
   your merchant ID and key when migrating.
   
3. Merchant ID and Merchant Key:(Mandatory) If any of these are not set and the 
   module is enabled, a disabled (gray) Checkout button appears on the Checkout 
   page. Set these values from your seller Google account under the 
   Settings->Integration tab. Separate Sandbox and Production.
   
4. .htaccess Basic Authentication Mode with PHP over CGI? If your site is 
   installed on a PHP CGI you must disable Basic Authentication over PHP. 
   To avoid spoofed messages (only if this feature is enabled) reaching 
   responsehandler.php, set the .htaccess file with the script linked 
   (http://<your-domain>/catalog/admin/htaccess.php). 
   Set permission 777 for http://<your-domain>/catalog/googlecheckout/ before 
   running the script. Remember change the permissions back after creating 
   the files.
   
5. Merchant Calculation Mode of Operation: Sets Merchant calculation URL for 
   Sandbox environment. Could be HTTP or HTTPS. (Checkout production environment 
   always requires HTTPS.)
   
6. Disable Google Checkout for Virtual Goods?: If this feature is enabled and
   there are any virtual goods in the cart, the Google Checkout button will be
   shown disabled (see: http://checkout.google.com/seller/policies.html#4).
    
7. Allow US PO BOX shipping: If this is set to false, you won't ship to any P.O. 
   box address in the US.

8. Default Values for Real Time Shipping Rates: Set your default values for 
   all merchant-calculated shipping rates. These values will be used if 
   Google Checkout cannot reach your API callback to calculate the shipping 
   price.

9. Google Checkout Carrier Calculated Shipping: Enable if you want to use CCS.
   Note that if you enable CCS, all merchant calculations will be ignored for
   Google Checkout orders. Only Flat Rate shipping rates will be included 
   with CCS.

10. Carrier Calculater Shipping Configuration: Sets default values, fix and 
    variable charge for each CCS method. The default value contains the default 
    shipping cost for a carrier-calculated-shipping option. If Google is unable 
    to obtain the carrier's shipping rate for a shipping option, the buyer will 
    still have the option of selecting that shipping option and paying the 
    default value to have the order shipped. See:

      http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#tag_price

    The fix value allows you to specify a fixed charge that will be added to the
    total cost of an order. See:

      http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#tag_additional-fixed-charge

    The variable charge specifies a percentage amount by which a carrier-calculated
    shipping rate will be adjusted. The tag's value may be positive or negative. See:

      http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#tag_additional-variable-charge-percent
    
    Set the default value to 0 to disable the method

11. Rounding Policy Mode and Rounding Policy Rule: Determines how Google Checkout
    will do rounding in prices.
    
    US default: rounding rule is TOTAL, rounding mode is HALF_EVEN
    UK default: rounding rule is PER_LINE, rounding mode is HALF_UP

    For more info, see:

      http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html

12. Cart Expiration Time: If different from NONE, this postive integer will set 
    a cart expiration time starting from the creation of the Google Button.
    This prevents buyers from submitting carts that may be deprecated or include
    bad settings. Default is NONE. Keep in mind that the time in your server may
    not be synchronized with the Google Checkout servers, so if you set a short
    expiration time, this may not be accuate.

13. Also send Notification with OSC: If enabled, the module will send an email 
    using the OSC internal email system if the comment in an order is longer 
    than 254 characters, the limit for a Google Checkout message. If this 
    happens, a warning will be shown in the Admin UI. It will also send emails 
    to the merchant account when orders states are changed in the Admin UI.

14. Google Analytics Id: Add Google Analytics to your osCommerce store.
    For more info, see below "Enabling E-Commerce Reporting for Google Analytics"
    below.

15. 3rd Party Tracking: Enable if you want to integrate the module 3rd party 
    tracking. Add the tracker URL or NONE to disable. For more info, see:

      http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html

16. Google Checkout restricted product categories: Include the IDs of all the 
    product categories that you want to exclude from Google Checkout purchases,
    separated by commas (ie. "1, 3,56 , 32"). For more information, see:

      http://checkout.google.com/support/sell/bin/answer.py?answer=46174&topic=8681
      http://checkout.google.com/seller/policies.html#4

17. Continue shopping URL: The URL customers will be redirected to if they 
    follow the link back to your site after checkout. The default value will 
    display a page showing the items purchases with Google Checkout:

      http://<your-domain>/catalog/googlecheckout/gc_return.php

Your Google Checkout setup is correct if upon creating a cart, a non-disabled 
Google Checkout button appears. See INSTALLATION.txt for more info.


ENABLING E-COMMERCE REPORTING FOR GOOGLE ANALYTICS
==================================================

To track Google Checkout orders, you must enable e-commerce reporting for your 
website in your Google Analytics account. The following steps explain how to
enable e-commerce reporting for your website:

1. Log in to your Google Analytics account.
2. Click the Edit link next to the profile you want to enable. This link     
   appears in the Settings column.
3. On the Profile Settings page, click the Edit link in the Main Website 
   Profile Information box.
4. Change the selected E-Commerce Website radio button from No to Yes.

For more info, see: 

  http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html

Note for 3rd party tracking: Actual configuration supports just one 3rd party 
tracking company. Some code modifications may be needed for specific trackers.

Please see:

  http://code.google.com/apis/checkout/developer/checkout_pixel_tracking.html
 
and change the code here:

  catalog/googlecheckout/gcheckout.php

The following mapping:

  $tracking_attr_types = array(
    'GC_attr_type1' => '3rd_attr_name1',
    'GC_attr_type2' => '3rd_attr_name2',
    ...
  );

Will translate to:

  <parameters>
    <url-parameter name="3rd_attr_name1" type="GC_attr_type1"/>
    <url-parameter name="3rd_attr_name2" type="GC_attr_type2"/>
    ...
  </parameters>

  
DIGITAL DELIVERY
================

All products marked as maked as "Product is Virtual" will be included in the 
digital delivery API. This means that at the end of the Google Checkout 
transaction, a link to the OSC checkout_success.php page will be shown as well 
as a description of the product.

If the whole cart is virtual, no options will be shown in the Google Checkout 
Place Order Page.

Note: If the cart is virtual but has some custom order_totals (low_orderFee, 
group_discount, etc), a notice saying an "Email will sent" will be shown for 
each order total in the Google Checkout confirmation page. This is a limitation 
of the Google Checkout API for supporting custom order totals and 
digital delivery.

 
MERCHANT CALCULATED SHIPPING
============================

In order to use this module you must have registered with areal time 
shipping provider, such as USPS or FedEx. This module must be activated and 
configured in Modules->Shipping. For each enabled module you'll have to set 
the default values in the Google Checkout Admin UI. The default values will be 
used if for any reason Google Checkout cannot reach your API callback to 
calculate the shipping price. 

The available shipping methods for each shipping provider must be configured in: 

  catalog/googlecheckout/library/shipping/merchant_calculated_methods.php

in the $mc_shipping_methods variable. If you want to disable one ore more 
methods, comment them out. Be aware that if you mix flat rate and real time 
rates, both will be treated as merchant-calculated shipping methods.

To configure new shipping methods, visit:

  http://<your-domain>/catalog/googlecheckout/tools/shipping/method_generator

For more info:

  http://<your-domain>/catalog/googlecheckout/tools/shipping/method_generator/README.txt


CARRIER CALCULATED SHIPPING
===========================

This feature allows merchants to get real time quotes without any effort. All
calculation are done by the Google Checkout servers. You only need to specify 
the shipping methods you want to provide. Google supports the carrier-calculated 
shipping feature for three carriers: 
 
* FedEx, 
* UPS 
* U.S. Postal Service (USPS)

You cannot offer carrier-calculated shipping methods and merchant-calculated
shipping methods for the same order. However, you can offer carrier-calculated
shipping methods and still use the Merchant Calculations API to calculate taxes 
and adjustments for coupons and gift certificates.
 
Please note that only US Domestic shipping address are allowed. If the shipping 
address is international, only the flat rate shipping method will be shown.

For more info, see:
 
  http://code.google.com/apis/checkout/developer/Google_Checkout_XML_API_Carrier_Calculated_Shipping.html#Process
 

TRACKING USERS AND ORDERS
=========================

In order to provide this support (as required for Level 2 integration), update
the API callback request field in the seller account to:

  https://<your-domain>/catalog/googlecheckout/responsehandler.php

Note that the production Checkout server requires an SSL callback site.
The Sandbox server accepts non-SSL callback URLs.

You can view your Google Checkout customers and their orders in the Reports tab. 
For each order, the default starting state is Google New. 

Explanation of Google Checkout order states in the OSC admin:

The instructions below assume you have enabled "Automatically authorize and
charge the buyer's credit card" in your Google Checkout merchant account 
(Settings->Preferences).

1. When an order for a regular (non-download) product is submitted through
   Google Checkout the order state in OSC admin will be automatically set to 
   "Google_Processing". When following up on (shipping) these orders you will 
   need to change the order status to "Google_Shipped".

2. When an order for a download product is submitted via Google Checkout, the 
   order status in the OSC admin will be auto set to "Google_Shipped" 
   (whether the customer has attempted to download or not). When following up 
   on download orders keep the status "Google_Shipped"; there is no need to 
   change the state.

3. Refunding/canceling "total" Google Checkout orders also works from within the 
   OSC admin.

  a. If you have not yet changed a non-download order from "Google_Processing"
     to "Google_Shipped" you can directly cancel/refund the TOTAL order by
     selecting "Google_Canceled" (but do NOT set to "Google_Refunded").

     Changing the state to "Google_Cancelled" will automatically refund 100% of
     the transaction AND cancel the order at the same time. So there is no need
     to login to your Google Checkout merchant account to process the TOTAL 
     refund.

  b. Also after an item is shipped you can refund the TOTAL amount of the order 
     by choosing "Google_Canceled" (but as stated above do NOT set to 
     "Google_Refunded").

  c. If you want to refund only a PORTION of the order amount (for
     example if customer ordered two items and you want to refund the amount for 
     only one item) then you will need to log in to your Google Checkout 
     merchant account and process the partial refund. Google will automatically 
     change the order status in OSC admin to "Google_Refunded" or 
     "Google_Shipped and Refunded", and you won't have to change the state 
     in the OSC admin.

  d. To reiterate: you should never need to manually change the order state
     to "Google_Refunded" or "Google_Shipped and Refunded." These states are 
     reserved for Google use only. 

4. You can add a Tracking Number during the state change from Google_Processing 
   to GoogleShipped. A text field and a combo with the shipping providers will 
   be shown when the order is in the Google_Processing state.
   
   Any comments added during state change will be sent to the buyer account page
   if you have selected the Append Comments option.

All statechanges are added as notes in the Admin UI. All request and response 
messages will be logged to the file: 

  catalog/googlecheckout/logs/response_message.log.

Refunds and cancellations are both added as new order totals in each order as 
well as part of the history of the order.


PROJECT HOME
============

To check out the latest release or to report issues, please visit:

  http://code.google.com/p/google-checkout-oscommerce


GROUP DISCUSSIONS
=================

To meet other developers and merchants who have integrated Google Checkout with 
osCommerce and to discuss any topics related to integration of Google Checkout 
with osCommerce, please visit the form:

  http://groups.google.com/group/google-checkout-for-osc-mod-support


MOST COMMON MISTAKES
====================

1. Make sure you set 777 permissions on the following files: 
    
    catalog/googlecheckout/logs/response_error.log
    catalog/googlecheckout/logs/response_message.log files.

2. Set the Google Checkout Merchant Center, set your callback URL to: 

     https://<your-domain/catalog/googlecheckout/responsehandler.php

   In Sandbox, HTTPS is not required. In Production mode, HTTPS is required.
   Set the correct option in the Google Checkout Admin UI. For more info about 
   supported SSL certificates, please see:
   
     http://www.google.com/checkout/ssl-certificates
     http://checkout.google.com/support/sell/bin/answer.py?answer=57856

3. Make sure you are using the correct combination of Merchant ID and 
   Merchant Key. Remember that Sandbox and Production Mode have different ones.
  

TROUBLESHOOTING
===============

1. Problem: 

     /public_html/googlecheckout/logs/response_message.log) [function.fopen]: 
     failed to open stream: Permission denied.

   Solution: 

     Set the file attribute to 777 for:
       
       catalog/googlecheckout/logs/response_error.log 
       catalog/googlecheckout/logs/response_message.log

2. Problem: 

     Test order shows up on Google Checkout but not in OSC admin.

   Solution: 

     There is an error somewhere in the file 
     catalog/googlecheckout/responsehandler.php or you have set a wrong API 
     callback function in your seller Google account under the 
     Settings->Integration tab.

3. Problem: 

     <error-message>Malformed URL component: expected id: 
     (\d{10})|(\d{15}), but got 8***********4 </error-message>
 
  Solution: 

    You have an extra space after your Google Merchant ID. Go to 
    Admin->Payment. Edit the Google Checkout module. The extra space should 
    disappear. Click the update button to save changes.

4. Problem: 

     <error-message>No seller found with id 7************8</error-message>
   
   Solution: 

     Wrong merchant id. Sandbox merchant id can only be use with 
     sandbox accounts. Sandbox and Live mode use different merchant id. 

5. Problem: 

     sun.security.validator.ValidatorException: PKIX path building 
     failed: sun.security.provider.certpath.SunCertPathBuilderE xception: unable 
     to find valid certification path to requested target

   Solution: 

     Your SSL certificate is not accepted by Google Checkout.
     Links for supported SSL certificates:

       http://www.google.com/checkout/ssl-certificates
       http://checkout.google.com/support/sell/bin/answer.py?answer=57856

6. Problem: 

     <error-message>Bad Signature on Cart</error-message>

   Solution: 

     Incorrect Merchant key.

7. Problem: 

     (/public_html/googlecheckout/logs/response_error.log) 
     Tue Nov 28 8:56:21 PST 2006:- Shopping cart not obtained from session. 
   
   Solution: 

     Set to False admin->configuration->session->Prevent Spider Sessions
     configuration. Side effects: You'll see spiders as active users.

   Solution 2 (Recommended): 

     Remove any string like 'jakarta' in the includes/spider.txt

8. Problem:
    
     Warning: main(admin/includes/configure.php) [function.main]: failed to open 
     stream: No such file or directory in 
     /public_html/googlecheckout/gcheckout.php on line 34
     Fatal error: main() [function.require]: Failed opening required 
     'admin/includes/configure.php' 
     (include_path='.:/usr/lib/php:/usr/local/lib/php')
     in /public_html/googlecheckout/gcheckout.php on line 33
   
   Solution:

     Change googlecheckout/gcheckout.php Line 34 'admin' for the modified admin
     directory require_once('admin/includes/configure.php');      

9. Problem:

     HTTP Authentication doesn't work with IIS.
     
   Solution:

     The PHP directive cgi.rfc2616_headers must be set to 0 (the default value). 
     Will use the $_SERVER['HTTP_AUTHORIZATION'] header.

11. Problem:

      No shipping is shown in the Cart.
     
    Solution:

      Check that the DIR_FS_CATALOG and DIR_WS_MODULES constants have the 
      correct values set in:
      
        catalog/includes/configre.php

      Try adding an echo in catalog/googlecheckout/gcheckout.php:

        echo $module_directory = DIR_FS_CATALOG . DIR_WS_MODULES . 'shipping/';
     
     and vist catalog/shopping_cart.php page see if the string you see is the 
     correct directory for the shipping files.


KNOWN BUGS
==========

Please report bugs at: 

  http://groups.google.com/group/google-checkout-for-osc-mod-support)


CHANGE LOG
==========

See CHANGELOG.txt.
