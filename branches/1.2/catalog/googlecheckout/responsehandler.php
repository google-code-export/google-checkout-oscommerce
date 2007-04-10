<?php
/*
  Copyright (C) 2006 Google Inc.

  Bugfixed and improved by Ryan of http://www.ubercart.org

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* **GOOGLE CHECKOUT ** v1.2
 * Script invoked for any callback notfications from the Checkout server
 * Can be used to process new order notifications, order state changes and risk notifications
 */
 
// 1. Setup the log file 
// 2. Parse the http header to verify the source
// 3. Parse the XML message
// 4. Trasfer control to appropriate function
error_reporting(0);

chdir('./..');
$curr_dir = getcwd();

define('API_CALLBACK_MESSAGE_LOG', $curr_dir .'/googlecheckout/response_message.log');
define('API_CALLBACK_ERROR_LOG', $curr_dir .'/googlecheckout/response_error.log');

define('API_SENT_MESSAGE_LOG', $curr_dir .'/googlecheckout/sent_message.log');

if(check_file('includes/modules/payment/googlecheckout.php')) {
  include_once('includes/modules/payment/googlecheckout.php');
}

require_once($curr_dir .'/googlecheckout/googlemerchantcalculations.php');
require_once($curr_dir .'/googlecheckout/googleresult.php');

if(check_file($curr_dir .'/googlecheckout/gcxmlparser.php')) {
  include_once($curr_dir .'/googlecheckout/gcxmlparser.php');
  include_once($curr_dir .'/googlecheckout/gcxmlbuilder.php');
}

// Setup the log files.
if (!$message_log = fopen(API_CALLBACK_MESSAGE_LOG, "a")) {
  error_func("Cannot open ". API_CALLBACK_MESSAGE_LOG ." file.\n", 0);
  exit(1);
}

// Retrieve the XML sent in the HTTP POST request to the response handler.
$xml_response = $HTTP_RAW_POST_DATA;

if (get_magic_quotes_gpc()) {
  $xml_response = stripslashes($xml_response);
}

fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"), $xml_response));
fwrite($message_log, sprintf("\n\rHTTP_USER_AGENT:- %s\n", getenv('HTTP_USER_AGENT')));

$xmlp = new gcXmlParser($xml_response);
$root = $xmlp->getRoot();
$data = $xmlp->getData();
fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"), $root));

// Restore the customer's session based on transmitted session ID.
if(isset($data[$root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'])) {
  $private_data = $data[$root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'];
  $sess_id = substr($private_data, 0, strpos($private_data, ";"));
  $sess_name = substr($private_data, strpos($private_data, ";") + 1);
  fwrite($message_log, sprintf("\r\n%s :- %s, %s\n", date("D M j G:i:s T Y"), $sess_id, $sess_name));
  // If session management is supported by this PHP version...
  if(function_exists('session_id'))
    session_id($sess_id);
  if(function_exists('session_name'))
    session_name($sess_name);
}
  
if(check_file('includes/application_top.php')) {
  include_once('includes/application_top.php');
}

if(tep_session_is_registered('cart') && is_object($cart)) {
  $cart->restore_contents();
} 
else {
  error_func("Shopping cart not obtained from session.\n");
  exit(1);	
}	

$googlepayment = new googlecheckout();
$merchant_id =  $googlepayment->merchantid;
$merchant_key = $googlepayment->merchantkey;

if(MODULE_PAYMENT_GOOGLECHECKOUT_CGI != 'True') {
	//Parse the HTTP header to verify the source.
	if(isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) && isset($HTTP_SERVER_VARS['PHP_AUTH_PW'])) {
	  $compare_mer_id = $HTTP_SERVER_VARS['PHP_AUTH_USER']; 
	  $compare_mer_key = $HTTP_SERVER_VARS['PHP_AUTH_PW'];
	}
	else {
	  error_func("HTTP Basic Authentication failed. Can't retrive Merchant Id/Key, Installed over CGI??\n");
	  exit(1);
	}
	
	if($compare_mer_id != $merchant_id || $compare_mer_key != $merchant_key) {
	  error_func("HTTP Basic Authentication failed. Wrong Merchant Id/Key Validation\n");
	  exit(1);
	}
}

switch ($root) {
  case "request-received":
    process_request_received_response($root, $data, $message_log);
    break;

  case "error":
    process_error_response($root, $data, $message_log);
    break;

  case "diagnosis":
    process_diagnosis_response($root, $data, $message_log);
    break;

  case "checkout-redirect":
    process_checkout_redirect($root, $data, $message_log);
    break;

  case "merchant-calculation-callback":
  	if(MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET == 'True') {
	  	include_once($curr_dir .'/googlecheckout/multisocket.php');
	  	process_merchant_calculation_callback($root, $data, $message_log, 2.7, false);
  		break;
  	}
  case "merchant-calculation-callback-single":
//  	set_time_limit(2);
    process_merchant_calculation_callback_single($root, $data, $message_log);
    break;

  case "new-order-notification":
    $orders_id = process_new_order_notification($root, $data, $googlepayment, $cart, $customer_id, $languages_id, $message_log);

    $cart->reset(true);
// Add the order details to the table
// This table could be modified to hold the merchant id and key if required 
// so that different mids and mkeys can be used for different orders
    tep_db_query("insert into " . $googlepayment->table_order . " values (" . $orders_id . ", ". gc_makeSqlString($data[$root]['google-order-number']['VALUE']) . ", " . gc_makeSqlFloat($data[$root]['order-total']['VALUE']) . ")");
    if(is_array($data[$root]['order-adjustment']['shipping'])) {
      foreach($data[$root]['order-adjustment']['shipping'] as $ship); {
         $shipping =  $ship['shipping-name']['VALUE'];
         $ship_cost = $ship['shipping-cost']['VALUE']; 
       }
    }
    $tax_amt = $data[$root]['order-adjustment']['total-tax']['VALUE'];
    $order_total = $data[$root]['order-total']['VALUE'];

    require(DIR_WS_CLASSES . 'order.php');
    $order = new order();	    
    // Load the selected shipping module.
    require(DIR_WS_CLASSES . 'shipping.php');
    $shipping_modules = new shipping($shipping);

    require_once(DIR_WS_LANGUAGES . $language . '/modules/payment/googlecheckout.php');

    // Update values so that order_total modules get the correct values.
    $order->info['total'] = $data[$root]['order-total']['VALUE'];
    $order->info['subtotal'] = $data[$root]['order-total']['VALUE'] - ($ship_cost + $tax_amt);
    $order->info['shipping_method'] = $shipping;
    $order->info['shipping_cost'] = $ship_cost;
    $order->info['tax_groups']['tax'] = $tax_amt ;  
    $order->info['currency'] = 'USD';
    $order->info['currency_value'] = 1;
    require(DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;
    $order_totals = $order_total_modules->process();

    for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
      $sql_data_array = array('orders_id' => gc_makeSqlInteger($orders_id),
                              'title' => gc_makeSqlString($order_totals[$i]['title']),
                              'text' => gc_makeSqlString($order_totals[$i]['text']),
                              'value' => gc_makeSqlString($order_totals[$i]['value']), 
                              'class' => gc_makeSqlString($order_totals[$i]['code']), 
                              'sort_order' => gc_makeSqlInteger($order_totals[$i]['sort_order']));
      tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
    }
    break;

  case "order-state-change-notification":
    process_order_state_change_notification($root, $data, $message_log, $googlepayment);
    break;

  case "charge-amount-notification":
    process_charge_amount_notification($root, $data, $message_log, $googlepayment);
    break;

  case "chargeback-amount-notification":
    process_chargeback_amount_notification($root, $data, $message_log);
    break;

  case "refund-amount-notification":
    process_refund_amount_notification($root, $data, $message_log);
    break;

  case "risk-information-notification":
    process_risk_information_notification($root, $data, $message_log, $googlepayment);
    break;

  default:
    $errstr = date("D M j G:i:s T Y") . " " . $root . ":- Invalid XML Method\n";
    error_log($errstr, 3, API_CALLBACK_ERROR_LOG);
    exit($errstr);
    break;

}

fclose($message_log);
exit(0);

// Log an error message to /catalog/googlecheckout/response_error.log.
function error_func($err_str, $mess_type = '3') {
  $err_str = date("D M j G:i:s T Y").":- ". $err_str. "\n";	
  error_log($err_str, $mess_type, API_CALLBACK_ERROR_LOG);
}

// Return true if $file exists, log a message and fail if it doesn't.
function check_file($file) {
  if(file_exists($file))
    return true;
  else {
    $err_str = date("D M j G:i:s T Y").":- ".$file. " does not exist" ;	
    error_log($err_str, 3, API_CALLBACK_ERROR_LOG);	
    exit(1);
  }
  return false;
}
		
function process_request_received_response($root, $data, $message_log) {
}

function process_error_response($root, $data, $message_log) {
}

function process_diagnosis_response($root, $data, $message_log) {
}

function process_checkout_redirect($root, $data, $message_log) {
}

function process_merchant_calculation_callback_single($root, $data, $message_log) {
  global $cart, $googlepayment, $order, $total_weight, $total_count;

//  $debug = fopen(API_SENT_MESSAGE_LOG, 'a');
//  fwrite($debug, 'Message received '. date("D M j G:i:s T Y") ."\n\n");


	// Get a hash array with the description of each shipping method 
	$methods_hash = $googlepayment->getMethods();

	require(DIR_WS_CLASSES .'order.php');
	$order = new order;

  // Register a random ID in the session to check throughout the checkout procedure
  // against alterations in the shopping cart contents.
  if (!tep_session_is_registered('cartID')) {
    tep_session_register('cartID');
  }
  $cartID = $cart->cartID;

  $total_weight = $cart->show_weight();
  $total_count = $cart->count_contents();

  // Create the results and send it.
  $merchant_calc = new GoogleMerchantCalculations();

  // Loop through the list of address ids from the callback.
  $addresses = gc_get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
	// Get all the enabled shipping methods.
  require(DIR_WS_CLASSES .'shipping.php');
	$shipping_modules = new shipping();
	
  // Required for some shipping methods (ie. USPS).
  require_once('includes/classes/http_client.php');
  foreach($addresses as $curr_address) {
    // Set up the order address.
    $curr_id = $curr_address['id'];
    $country = $curr_address['country-code']['VALUE'];
    $city = $curr_address['city']['VALUE'];
    $region = $curr_address['region']['VALUE'];
    $postal_code = $curr_address['postal-code']['VALUE'];

    $row = tep_db_fetch_array(tep_db_query("select * from ". TABLE_COUNTRIES ." where countries_iso_code_2 = '". gc_makeSqlString($country) ."'"));
    $order->delivery['country'] = array('id' => $row['countries_id'], 
                                        'title' => $row['countries_name'], 
                                        'iso_code_2' => $country, 
                                        'iso_code_3' => $row['countries_iso_code_3']);
    $order->delivery['country_id'] = $row['countries_id'];
    $order->delivery['format_id'] = $row['address_format_id'];
	
    $row = tep_db_fetch_array(tep_db_query("select * from ". TABLE_ZONES ." where zone_code = '" . gc_makeSqlString($region) ."'"));
    $order->delivery['zone_id'] = $row['zone_id'];
    $order->delivery['state'] = $row['zone_name'];

    $order->delivery['city'] = $city;
    $order->delivery['postcode'] = $postal_code;
	
    // Loop through each shipping method to see if merchant-calculated shipping support is to be provided.
			if(isset($data[$root]['calculate']['shipping'])) {
		        $shipping = gc_get_arr_result($data[$root]['calculate']['shipping']['method']);


						if(MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET == 'True') {
	// Single
		         	$name = $shipping[0]['name'];
	//            Compute the price for this shipping method and address id
			        list($a, $method_name) = explode(': ',$name);
							if((($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) && ($methods_hash[$method_name][1] == 'domestic_types'))
									||
								(($order->delivery['country']['id'] != SHIPPING_ORIGIN_COUNTRY) && ($methods_hash[$method_name][1] == 'international_types'))){
	//								reset the shipping class to set the new address
										if (class_exists($methods_hash[$method_name][2])) {			        	
					        		$GLOBALS[$methods_hash[$method_name][2]] = new $methods_hash[$method_name][2];
										}
							}
//			        $shipping_modules = new shipping();
			      	$quotes =  $shipping_modules->quote('', $methods_hash[$method_name][2]);
			        
						}
						else {
	// Standard
			        foreach($shipping as $curr_ship) {
			         	$name = $curr_ship['name'];
	//            Compute the price for this shipping method and address id
				        list($a, $method_name) = explode(': ',$name);
								if((($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) && ($methods_hash[$method_name][1] == 'domestic_types'))
										||
									(($order->delivery['country']['id'] != SHIPPING_ORIGIN_COUNTRY) && ($methods_hash[$method_name][1] == 'international_types'))){
			//								reset the shipping class to set the new address
											if (class_exists($methods_hash[$method_name][2])) {			        	
						        		$GLOBALS[$methods_hash[$method_name][2]] = new $methods_hash[$method_name][2];
											}
								}
			        }
							$quotes =  $shipping_modules->quote();
						}
						reset($shipping);
		        foreach($shipping as $curr_ship) {
		         	$name = $curr_ship['name'];
//            Compute the price for this shipping method and address id
			        list($a, $method_name) = explode(': ',$name);
							unset($quote_povider);
							unset($quote_method);
							if((($order->delivery['country']['id'] == SHIPPING_ORIGIN_COUNTRY) && ($methods_hash[$method_name][1] == 'domestic_types'))
									||
								(($order->delivery['country']['id'] != SHIPPING_ORIGIN_COUNTRY) && ($methods_hash[$method_name][1] == 'international_types'))){
								foreach($quotes as $key_provider => $shipping_provider) {
									// privider name (class)
									if($shipping_provider['id'] == $methods_hash[$method_name][2]) {
										// method name			
										$quote_povider = $key_provider;
										if(is_array($shipping_provider['methods']))
										foreach($shipping_provider['methods'] as $key_method => $shipping_method) {
											if($shipping_method['id'] == $methods_hash[$method_name][0]){
												$quote_method = $key_method;
												break;
											}										
										}
										break;
									}
								}
							}
							
//	            print_r($quotes);
	            //if there is a problem with the method, i mark it as non-shippable
	            if( isset($quotes[$quote_povider]['error']) || !isset($quotes[$quote_povider]['methods'][$quote_method]['cost'])) {
	            	$price = "9999.99";
	            	$shippable = "false";
	            }
	            else {
	            	$price = $quotes[$quote_povider]['methods'][$quote_method]['cost'];
	            	$shippable = "true";
	            }
	            
	            $merchant_result = new GoogleResult($curr_id);
	            $merchant_result->SetShippingDetails($name, $price, "USD", $shippable);
	
	            if($data[$root]['calculate']['tax']['VALUE'] == "true") {
	              //Compute tax for this address id and shipping type
	              $amount = 15; // Modify this to the actual tax value
	              $merchant_result->SetTaxDetails($amount, "USD");
	            }
	
	            $codes = gc_get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
	            foreach($codes as $curr_code) {
	              //Update this data as required to set whether the coupon is valid, the code and the amount
	              $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "USD", "test2");
	              $merchant_result->AddCoupons($coupons);
	            }
	            $merchant_calc->AddResult($merchant_result);
	          }
	        }
    else {
      $merchant_result = new GoogleResult($curr_id);
      if($data[$root]['calculate']['tax']['VALUE'] == 'true') {
        // Compute tax for this shipping type and address ID.
        $amount = 15; // Modify this to the actual tax value
        $merchant_result->SetTaxDetails($amount, 'USD');
      }
      $codes = gc_get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
      foreach($codes as $curr_code) {
        // Update this data as required to set whether the coupon is valid, the code, and the amount.
        $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "USD", "test2");
        $merchant_result->AddCoupons($coupons);
		  }
		  $merchant_calc->AddResult($merchant_result);
	  }
  }

//  $mess = 'Response sent '. date("D M j G:i:s T Y") ."\n\n". $merchant_calc->getXML() ."\n\n";
//  fwrite($debug, $mess);

  echo $merchant_calc->getXML();
}

/**
 * New order notifications come through when someone checks out through your store:
 * 1. Get cart contents
 * 2. Add a row in orders table
 * 3. Add a row for each product in orders_products table
 * 4. Add rows if required to orders_products_attributes table
 * 5. Add a row to orders_status_history and orders_total
 * 6. Check stock configuration and update inventory if required
 */
function process_new_order_notification($root, $data, $googlepayment, $cart, $customer_id, $languages_id, $message_log) {
  // If buyer has logged in, use their customer ID for the order.
  if(isset($customer_id) && $customer_id != '') {
    $cust_id = $customer_id;
    $oper = 'update';
    $params = ' customers_id = '. $cust_id;
  }
  else {
    // Else check if buyer is a new user from Google Checkout
    $customer_info = tep_db_fetch_array(tep_db_query("select customers_id from ". $googlepayment->table_name ." where buyer_id = ". gc_makeSqlString($data[$root]['buyer-id']['VALUE'])));
    if ($customer_info['customers_id'] == '')  {
      // If the user does not exist in google_checkout, see if we can find an existing user account.
      $customer_info = tep_db_fetch_array(tep_db_query("select customers_id from ". TABLE_CUSTOMERS ." where customers_email_address = '". gc_makeSqlString($data[$root]['buyer-shipping-address']['email']['VALUE']) ."'"));
      if (intval($customer_info['customers_id']) > 0) {
        $cust_id = $customer_info['customers_id'];
        tep_db_query("insert into ". $googlepayment->table_name ." values ( ". $cust_id .", ". $data[$root]['buyer-id']['VALUE'] .")");
        $oper = 'update';
        $params = ' customers_id = '. $cust_id;
      }
      else {
        // If no customer or google_checkout entry exists, create a new account.
        $custname = gc_split_customer_name($data[$root]['buyer-shipping-address']['contact-name']['VALUE']);
        $sql_data_array = array('customers_gender' => '',
                                'customers_firstname' => $custname['first'],
                                'customers_lastname' => $custname['last'],
                                'customers_dob' => '',
                                'customers_email_address' => gc_makeSqlString($data[$root]['buyer-shipping-address']['email']['VALUE']),
                                'customers_default_address_id' => 0,
                                'customers_telephone' => gc_makeSqlString($data[$root]['buyer-billing-address']['phone']['VALUE']),
                                'customers_fax' => gc_makeSqlString($data[$root]['buyer-shipping-address']['fax']['VALUE']),
                                'customers_password' => tep_encrypt_password(gc_makeSqlString($data[$root]['buyer-id']['VALUE'])), 
                                'customers_newsletter' => '');
        tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);
        $cust_id = tep_db_insert_id();
        $sql_data_array = array('customers_info_id' => $cust_id,
                                'customers_info_date_of_last_logon' => 'null',
                                'customers_info_number_of_logons' => 0,
                                'customers_info_date_account_created' => 'now()',
                                'customers_info_date_account_last_modified' => 'null',
                                'global_product_notifications' => '');
        tep_db_perform(TABLE_CUSTOMERS_INFO, $sql_data_array);
        tep_db_query("insert into ". $googlepayment->table_name ." values ( ". $cust_id .", ". $data[$root]['buyer-id']['VALUE'] .")");
        $oper = 'insert';
        $params = '';
      }
    }
    else {
      $cust_id = $customer_info['customers_id'];
      $oper = 'update';
      $params = ' customers_id = '. $cust_id;
    }
  }

  // Update address book with the latest entry.
  // (This has the disadvantage of overwriting an existing address book entry of the user.)
  $country_code = trim($data[$root]['buyer-shipping-address']['country-code']['VALUE']);
  $country_answer = tep_db_fetch_array(tep_db_query("select countries_id from ". TABLE_COUNTRIES ." where countries_iso_code_". strlen($country_code) ." = '". $country_code ."'"));
  $zone_answer = tep_db_fetch_array(tep_db_query("select zone_id from ". TABLE_ZONES ." where zone_code = '". $data[$root]['buyer-shipping-address']['region']['VALUE'] ."'")); 

  $custname = gc_split_customer_name($data[$root]['buyer-shipping-address']['contact-name']['VALUE']);
  $sql_data_array = array('customers_id' => $cust_id,
                          'entry_gender' => '',
                          'entry_company' => gc_makeSqlString($data[$root]['buyer-shipping-address']['company-name']['VALUE']),
                          'entry_firstname' => $custname['first'],
                          'entry_lastname' => $custname['last'],
                          'entry_street_address' => gc_makeSqlString($data[$root]['buyer-shipping-address']['address1']['VALUE']),
      	                  'entry_suburb' => gc_makeSqlString($data[$root]['buyer-shipping-address']['address2']['VALUE']),
                          'entry_postcode' => gc_makeSqlString($data[$root]['buyer-shipping-address']['postal-code']['VALUE']),
                          'entry_city' => gc_makeSqlString($data[$root]['buyer-shipping-address']['city']['VALUE']),
                          'entry_state' => gc_makeSqlString($data[$root]['buyer-shipping-address']['region']['VALUE']),
                          'entry_country_id' => gc_makeSqlInteger($country_answer['countries_id']),
                          'entry_zone_id' => gc_makeSqlInteger($zone_answer['zone_id']));
  tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, $oper, $params);	

  if($oper == 'insert') {
    $address_book_id = tep_db_insert_id();
    tep_db_query('update '. TABLE_CUSTOMERS .' set customers_default_address_id= '. $address_book_id .' where customers_id = '. $cust_id);
  }

  // Insert entry into orders table.
  $sql_data_array = array('customers_id' => $cust_id,
                          'customers_name' => $data[$root]['buyer-shipping-address']['contact-name']['VALUE'],
                          'customers_company' => $data[$root]['buyer-shipping-address']['company-name']['VALUE'],
                          'customers_street_address' => $data[$root]['buyer-shipping-address']['address1']['VALUE'],
                          'customers_suburb' => $data[$root]['buyer-shipping-address']['address2']['VALUE'],
                          'customers_city' => $data[$root]['buyer-shipping-address']['city']['VALUE'],
                          'customers_postcode' => $data[$root]['buyer-shipping-address']['postal-code']['VALUE'], 
                          'customers_state' => $data[$root]['buyer-shipping-address']['region']['VALUE'],
                          'customers_country' => $data[$root]['buyer-shipping-address']['country-code']['VALUE'],
                          'customers_telephone' => $data[$root]['buyer-billing-address']['phone']['VALUE'], 
                          'customers_email_address' => $data[$root]['buyer-shipping-address']['email']['VALUE'],
                          'customers_address_format_id' => 2, 
                          'delivery_name' => $data[$root]['buyer-shipping-address']['contact-name']['VALUE'], 
                          'delivery_company' => $data[$root]['buyer-shipping-address']['company-name']['VALUE'],
                          'delivery_street_address' => $data[$root]['buyer-shipping-address']['address1']['VALUE'], 
                          'delivery_suburb' => $data[$root]['buyer-shipping-address']['address2']['VALUE'], 
                          'delivery_city' => $data[$root]['buyer-shipping-address']['city']['VALUE'], 
                          'delivery_postcode' => $data[$root]['buyer-shipping-address']['postal-code']['VALUE'], 
                          'delivery_state' => $data[$root]['buyer-shipping-address']['region']['VALUE'], 
                          'delivery_country' => $data[$root]['buyer-shipping-address']['country-code']['VALUE'], 
                          'delivery_address_format_id' => 2, 
                          'billing_name' => gc_makeSqlString($data[$root]['buyer-billing-address']['contact-name']['VALUE']), 
                          'billing_company' => gc_makeSqlString($data[$root]['buyer-billing-address']['company-name']['VALUE']),
                          'billing_street_address' => gc_makeSqlString($data[$root]['buyer-billing-address']['address1']['VALUE']), 
                          'billing_suburb' => gc_makeSqlString($data[$root]['buyer-billing-address']['address2']['VALUE']), 
                          'billing_city' => gc_makeSqlString($data[$root]['buyer-billing-address']['city']['VALUE']), 
                          'billing_postcode' => gc_makeSqlString($data[$root]['buyer-billing-address']['postal-code']['VALUE']), 
                          'billing_state' => gc_makeSqlString($data[$root]['buyer-billing-address']['region']['VALUE']), 
                          'billing_country' => gc_makeSqlString($data[$root]['buyer-billing-address']['country-code']['VALUE']), 
                          'billing_address_format_id' => 2, 
                          'payment_method' => 'Google Checkout',
                          'cc_type' => '', 
                          'cc_owner' => '', 
                          'cc_number' => '', 
                          'cc_expires' => '', 
                          'date_purchased' => gc_makeSqlString($data[$root]['timestamp']['VALUE']), 
                          'orders_status' => 1, 
                          'currency' => 'USD',
                          'currency_value' => 1);
  tep_db_perform(TABLE_ORDERS, $sql_data_array);

  // Insert entries into orders_products.
  $orders_id = tep_db_insert_id();
  
  
  $items = gc_get_arr_result($data[$root]['shopping-cart']['items']['item']);
  $products = array();
  foreach($items as $item){
  	$products[] = unserialize(base64_decode($item['merchant-private-item-data']['VALUE']));
  }
  
  $orders_id = tep_db_insert_id();						
  for ($i = 0; $i < sizeof($products); $i++) {
    $tax_answer = tep_db_fetch_array(tep_db_query("select tax_rate from ". TABLE_TAX_RATES ." as tr, ". TABLE_ZONES ." as z, ". TABLE_ZONES_TO_GEO_ZONES ." as ztgz where z.zone_code = '". $data[$root]['buyer-shipping-address']['region']['VALUE'] ."' and z.zone_id = ztgz.zone_id and tr.tax_zone_id = ztgz.geo_zone_id and tax_class_id = ". $products[$i]['tax_class_id']));
    $products_tax = $tax_answer['tax_rate'];

    $sql_data_array = array('orders_id' => $orders_id,
                            'products_id' => gc_makeSqlInteger($products[$i]['id']),
                            'products_model' => gc_makeSqlString($products[$i]['model']),
                            'products_name' => gc_makeSqlString($products[$i]['name']),
                            'products_price' => gc_makeSqlFloat($products[$i]['price']),
                            'final_price' => gc_makeSqlFloat($products[$i]['final_price']),
                            'products_tax' => gc_makeSqlFloat($products_tax), 
                            'products_quantity' => gc_makeSqlInteger($products[$i]['quantity'])); 
    tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);	

    //Insert entries into orders_products_attributes								
    $orders_products_id = tep_db_insert_id();
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
      while (list($option, $value) = each($products[$i]['attributes'])) {
        $query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, "
                ."pa.price_prefix from ". TABLE_PRODUCTS_OPTIONS ." popt, ". TABLE_PRODUCTS_OPTIONS_VALUES
                ." poval, ". TABLE_PRODUCTS_ATTRIBUTES ." pa where pa.products_id = '". $products[$i]['id']
                ."' and pa.options_id = '" . gc_makeSqlString($option) . "' and pa.options_id = popt.products_options_id "
                ."and pa.options_values_id = '" . gc_makeSqlString($value) . "' and pa.options_values_id = poval.products_options_values_id "
                ."and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'";
        $attributes = tep_db_fetch_array(tep_db_query($query));

        $sql_data_array = array('orders_id' => $orders_id,
                                'orders_products_id' => $orders_products_id,
                                'products_options' => gc_makeSqlString($attributes['products_options_name']),
                                'products_options_values' => gc_makeSqlString($attributes['products_options_values_name']),
                                'options_values_price' => gc_makeSqlFloat($attributes['options_values_price']),
                                'price_prefix' => gc_makeSqlString($attributes['price_prefix'])); 
        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);	
      }		
    }		
            
    // Update inventory and ordered values in products table.
    $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS
                              . " where products_id = '". $products[$i]['id'] . "'");

    if (tep_db_num_rows($stock_query) > 0) {
      tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = products_quantity - "
                 . $products[$i]['quantity'] . ", products_ordered = products_ordered + "
                 . $products[$i]['quantity'] . " where products_id = '" . $products[$i]['id'] . "'");
      $stock_values = tep_db_fetch_array($stock_query);
      $stock_left = $stock_values['products_quantity'] - $products[$i]['quantity'];
      if ($stock_left < 1 && STOCK_ALLOW_CHECKOUT == 'false') {
        tep_db_query("update ". TABLE_PRODUCTS ." set products_status = '0' where products_id = '". $products[$i]['id'] ."'");
      }
    }
  }
    
  //Insert entry into orders_status_history		
  $sql_data_array = array('orders_id' => $orders_id,
                          'orders_status_id' => 1,
                          'date_added' => 'now()',
                          'customer_notified' => 1,
                           'comments' => 'Google Checkout Order No: ' . $data[$root]['google-order-number']['VALUE']. "\n" .
                           'Merchant Calculations used: '. ((@$data[$root]['order-adjustment']['merchant-calculation-successful']['VALUE'] == 'true')?'True':'False') . "\n" .
                           'Buyer\'s User: ' . $data[$root]['buyer-billing-address']['email']['VALUE'] . "\n" .
                           'Buyer\'s Password: ' .  $data[$root]['buyer-id']['VALUE']
                           );  //Add Order number to Comments box. For customer's reference.
  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);	

  gc_send_ack();
  return $orders_id;
}

function process_order_state_change_notification($root, $data, $message_log, $googlepayment) {
  $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
  $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];

  $previous_financial_state = $data[$root]['previous-financial-order-state']['VALUE'];
  $previous_fulfillment_order = $data[$root]['previous-fulfillment-order-state']['VALUE'];

  $google_order_number = $data[$root]['google-order-number']['VALUE'];
    
  $google_order = tep_db_fetch_array(tep_db_query("select orders_id from ". $googlepayment->table_order ." where google_order_number = '". gc_makeSqlString($google_order_number) ."'"));
		
  fwrite($message_log,sprintf("\n%s\n", $data[$root]['new-financial-order-state']['VALUE']));

  $update = false;
  if($previous_financial_state != $new_financial_state) {
    switch($new_financial_state) {
      case 'REVIEWING':
        break;

      case 'CHARGEABLE':
				$update = true;
				$orders_status_id = 1;
				$comments = 'Time: '. $data[$root]['timestamp']['VALUE'] ."\nNew state: ". $new_financial_state ."\nOrder is ready to be charged.";
				$customer_notified = 0;
        break;

      case 'CHARGING':
        break;

      case 'CHARGED':
				$update = true;
				$orders_status_id = 2;
				$comments = 'Time: '. $data[$root]['timestamp']['VALUE'] ."\nNew state: ". $new_financial_state ;
				$customer_notified = 0;
        break;
      

      case 'PAYMENT-DECLINED':
				$update = true;
				$orders_status_id = 1;
				$customer_notified = 1;
				$comments = 'Time: '. $data[$root]['timestamp']['VALUE'] ."\nNew state: ". $new_financial_state .'Payment was declined. Waiting for buyer to update his credit card... DON\'T Deliver'; 
        break;

      case 'CANCELLED':
				$update = true;
				$orders_status_id = 1;
				$customer_notified = 1;
				$comments = 'Time: '. $data[$root]['timestamp']['VALUE'] ."\nNew state: ". $new_financial_state ."\nOrder was canceled\nReason: ". $data[$root]['reason']['VALUE']; 
        break;

      case 'CANCELLED_BY_GOOGLE':
				$update = true;
				$orders_status_id = 1;
				$customer_notified = 1;
				$comments = 'Time: '. $data[$root]['timestamp']['VALUE'] ."\nNew state: ". $new_financial_state ."\nOrder was canceled by Google\nReason: ". $data[$root]['reason']['VALUE']; 
        break;

      default:
        break;
    }
  }  

  if($update) {
    $sql_data_array = array('orders_id' => $google_order['orders_id'],
                            'orders_status_id' => $orders_status_id,
                            'date_added' => 'now()',
                            'customer_notified' => $customer_notified,
	                          'comments' => $comments);
    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    tep_db_query("update ". TABLE_ORDERS . " set orders_status = '".$orders_status_id."' where orders_id = '". gc_makeSqlInteger($google_order['orders_id']) ."'");
  }
    
  $update = false;   

  if($previous_fulfillment_order != $new_fulfillment_order) {
    switch($new_fulfillment_order) {
      case 'NEW':
        break;

      case 'PROCESSING':
        break;

      case 'DELIVERED':
				$update = true;
				$orders_status_id = 3;
				$comments = 'Time: '. $data[$root]['timestamp']['VALUE'] ."\nNew state: ". $new_fulfillment_order ."\nOrder was Delivered.\n";
				$customer_notified = 1;
        break;

      case 'WILL_NOT_DELIVER':
        break;

      default:
         break;
    }
  }

  if($update) {
    $sql_data_array = array('orders_id' => $google_order['orders_id'],
                            'orders_status_id' => $orders_status_id,
                            'date_added' => 'now()',
	                          'customer_notified' => $customer_notified,
	                          'comments' => $comments);
    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    tep_db_query("update ". TABLE_ORDERS ." set orders_status = '". $orders_status_id ."' WHERE orders_id = '". gc_makeSqlInteger($google_order['orders_id']) ."'");
  }

  gc_send_ack();	  
}  

// Update the order status upon completion of payment.
function process_charge_amount_notification($root, $data, $message_log, $googlepayment) {
  $google_order_number = $data[$root]['google-order-number']['VALUE'];
  $google_order = tep_db_fetch_array(tep_db_query("select orders_id from ". $googlepayment->table_order ." where google_order_number = '". gc_makeSqlString($google_order_number) ."'"));

  $sql_data_array = array('orders_id' => $google_order['orders_id'],
                          'orders_status_id' => 2,
                          'date_added' => 'now()',
                          'customer_notified' => 0,
                          'comments' => 'Latest charge amount: '. $data[$root]['latest-charge-amount']['currency'] .' '. $data[$root]['latest-charge-amount']['VALUE'] ."\nTotal charge amount: ". $data[$root]['latest-charge-amount']['currency'] .' '. $data[$root]['total-charge-amount']['VALUE']);  	
  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  // Adjust the orders_status value here if you need it to move to a different status upon payment.
  tep_db_query("update ". TABLE_ORDERS ." set orders_status = 2 where orders_id = '". gc_makeSqlInteger($google_order['orders_id']) ."'");

  gc_send_ack();
}

function process_chargeback_amount_notification($root, $data, $message_log) {
  gc_send_ack(); 
}

function process_refund_amount_notification($root, $data, $message_log) {
  gc_send_ack(); 
}

// Set an order back to pending if there's a problem with payment.
function process_risk_information_notification($root, $data, $message_log, $googlepayment) {
  $google_order_number = $data[$root]['google-order-number']['VALUE'];
  $google_order = tep_db_fetch_array(tep_db_query("select orders_id from ". $googlepayment->table_order ." where google_order_number = '". gc_makeSqlString($google_order_number) ."'"));

  $sql_data_array = array('orders_id' => $google_order['orders_id'],
                          'orders_status_id' => 1,
                          'date_added' => 'now()',
                          'customer_notified' => 0,
                          'comments' => "Risk Information: \n"
                                       .' Elegible for Protection: '. $data[$root]['risk-information']['eligible-for-protection']['VALUE'] ."\n"
                                       .' Avs Response: '. $data[$root]['risk-information']['avs-response']['VALUE'] ."\n"
                                       .' Cvn Response: '. $data[$root]['risk-information']['cvn-response']['VALUE'] ."\n"
                                       .' Partial CC number: '. $data[$root]['risk-information']['partial-cc-number']['VALUE'] ."\n"
                                       .' Buyer account age: '. $data[$root]['risk-information']['buyer-account-age']['VALUE'] ."\n"
                                       .' IP Address: '. $data[$root]['risk-information']['ip-address']['VALUE'] ."\n");
  tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
  tep_db_query("update ". TABLE_ORDERS ." set orders_status = '". 1 ."' WHERE orders_id = '".gc_makeSqlInteger($google_order['orders_id'])."'");

  gc_send_ack();
}
  
function gc_send_ack() {
  $acknowledgment = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
                   ."<notification-acknowledgment xmlns=\"http://checkout.google.com/schema/2\"/>";
  echo $acknowledgment;
}

//Functions to prevent SQL injection attacks
function gc_makeSqlString($str) {
  return addcslashes(stripcslashes($str), "'\"\\\0..\37!@\@\177..\377");
}

function gc_makeSqlInteger($val) {
  return ((settype($val, 'integer'))?($val):0); 
}

function gc_makeSqlFloat($val) {
  return ((settype($val, 'float'))?($val):0); 
}

/**
 * In case the XML API contains multiple open tags with the same value, then invoke this function and
 * perform a foreach on the resultant array. This takes care of cases when there is only one unique tag
 * or multiple tags. Examples of this are "anonymous-address", "merchant-code-string" from the 
 * merchant-calculations-callback API.
 */
function gc_get_arr_result($child_node) {
  $result = array();
  if(isset($child_node)) {
    if(gc_is_associative_array($child_node)) {
      $result[] = $child_node;
    }
    else {
      foreach($child_node as $curr_node){
        $result[] = $curr_node;
      }
    }
  }

  return $result;
}

// Returns true if a given variable represents an associative array
function gc_is_associative_array($var) {
  return is_array($var) && !is_numeric(implode('', array_keys($var)));
} 

// Return an array that splits a customer name into first and last names.
function gc_split_customer_name($name) {
  $offset = strrpos(rtrim($name), ' ');
  if ($offset === false) {
    return array('first' => gc_makeSqlString($name), 'last' => '');
  }
  else {
    return array('first' => gc_makeSqlString(substr($name, 0, $offset)),
                 'last' => gc_makeSqlString(substr($name, $offset + 1)));
  }
}

?>