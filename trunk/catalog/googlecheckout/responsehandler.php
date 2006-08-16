<?php
/*
  Copyright (C) 2006 Google Inc.

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

/* **GOOGLE CHECKOUT **
 * Script invoked for any callback notfications from the Checkout server
 * Can be used to process new order notifications, order state changes and risk notifications
 */
 
// 1. Setup the log file 
// 2. Parse the http header to verify the source
// 3. Parse the XML message
// 4. Trasfer control to appropriate function 

  chdir('./..');
	$curr_dir = getcwd();
  define('API_CALLBACK_MESSAGE_LOG', $curr_dir."/googlecheckout/response_message.log");
	define('API_CALLBACK_ERROR_LOG', $curr_dir."/googlecheckout/response_error.log");

	if(check_file('includes/modules/payment/googlecheckout.php'))
    include_once('includes/modules/payment/googlecheckout.php');
  
	if(check_file($curr_dir. '/googlecheckout/xmlparser.php'))
    include_once($curr_dir.'/googlecheckout/xmlparser.php');
	
//Setup the log files
  if (!$message_log = fopen(API_CALLBACK_MESSAGE_LOG, "a")) {
    error_func("Cannot open " . API_CALLBACK_MESSAGE_LOG . " file.\n", 0);
		exit(1);
  }

// Retrieve the XML sent in the HTTP POST request to the ResponseHandler
  $xml_response = $HTTP_RAW_POST_DATA;
  if (get_magic_quotes_gpc()) {
    $xml_response = stripslashes($xml_response);
  }
  fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"),$xml_response));
  
	$xmlp = new XmlParser($xml_response);
	$root = $xmlp->getRoot();
	$data = $xmlp->getData();
	fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"), $root));	  
		
  if(isset($data[$root]['shopping-cart']['merchant-private-data']['session-data'])) {
	  $private_data = $data[$root]['shopping-cart']['merchant-private-data']['session-data'];
	  $sess_id = substr($private_data, 0 , strpos($private_data,";"));
		$sess_name = substr($private_data, strpos($private_data,";")+1);
    fwrite($message_log, sprintf("\r\n%s :- %s, %s\n",date("D M j G:i:s T Y"), $sess_id, $sess_name));						
    if(function_exists('session_id'))
		  session_id($sess_id);
		if(function_exists('session_name'))	
		  session_name($sess_name);  
	}
	if(check_file('includes/application_top.php'))
    include_once('includes/application_top.php');
	if(tep_session_is_registered('cart') && is_object($cart)) {
	  $cart->restore_contents();
	} 
	else {
		error_func("Shopping cart not obtained from session.\n");
		exit(1);	
	}	
			
//Parse the http header to verify the source
  $headers = getallheaders();
	if(isset($headers['Authorization'])) {
    $auth_encode = $headers['Authorization'];
    $auth = base64_decode(substr($auth_encode, strpos($auth_encode, " ") + 1));
    $compare_mer_id = substr($auth, 0, strpos($auth,":"));
    $compare_mer_key = substr($auth, strpos($auth,":")+1);
	} else {
		error_func("HTTP Basic Authentication failed.\n");
		exit(1);
	}
	$googlepayment = new googlecheckout();
	$merchant_id =  $googlepayment->merchantid;
	$merchant_key = $googlepayment->merchantkey;
	
	if($compare_mer_id != $merchant_id || $compare_mer_key != $merchant_key) {
		error_func("HTTP Basic Authentication failed.\n");
		exit(1);
	}

  switch ($root) {
    case "request-received": {
      process_request_received_response($root, $data, $message_log);
      break;
    }
    case "error": {
      process_error_response($root, $data, $message_log);
      break;
    }
    case "diagnosis": {
      process_diagnosis_response($root, $data, $message_log);
      break;
    }
    case "checkout-redirect": {
      process_checkout_redirect($root, $data, $message_log);
      break;
    } 
    case "merchant-calculation-callback": {
      process_merchant_calculation_callback($root, $data, $message_log);
      break;
    } 
    case "new-order-notification": {
			$new_cart = new shoppingCart;
			$product_list = $data[$root]['shopping-cart']['merchant-private-data']['product-data'];
//Retrieve the list of product ids to get the contents of the cart when it was posted 		
			$tok = strtok($product_list, ";");
	    while($tok != FALSE) {
	      $product_id = $tok;
				$new_cart->add_cart($product_id);
		    $tok = strtok(";");
	    }  
//Reset the cart stored in the session
	    $cart->reset(TRUE);		
			$cart = $new_cart;
			$orders_id = process_new_order_notification($root, $data, $googlepayment, $new_cart, $customer_id, $languages_id, $message_log);
			
//Add the order details to the table
// This table could be modified to hold the merchant id and key if required 
// so that different mids and mkeys can be used for different orders
			tep_db_query("insert into " . $googlepayment->table_order . " values (" . $orders_id . ", ". $data[$root]['google-order-number'] . ", " . $data[$root]['order-total'] . ")");
			foreach($data[$root]['order-adjustment']['shipping'] as $ship); {
			  $shipping =  $ship['shipping-name'];
				$ship_cost = $ship['shipping-cost']; 
			}
			$tax_amt = $data[$root]['order-adjustment']['total-tax'];
			$order_total = $data[$root]['order-total'];
 				
			require(DIR_WS_CLASSES . 'order.php');
			$order = new order();	    
     
// load the selected shipping module
      require(DIR_WS_CLASSES . 'shipping.php');
      $shipping_modules = new shipping($shipping);
// Update values so that order_total modules get the correct values 			
			$payment= "GoogleCheckout";
			$order->info['total'] = $data[$root]['order-total'];
			$order->info['subtotal'] = $data[$root]['order-total'] - ($ship_cost + $tax_amt);
			$order->info['shipping_method'] = $shipping;
			$order->info['shipping_cost'] = $ship_cost;
			$order->info['tax_groups']['tax'] = $tax_amt ;  
			$order->info['currency'] = 'USD';
			$order->info['currency_value'] = 1;
			
			require(DIR_WS_CLASSES . 'order_total.php');
		  $order_total_modules = new order_total;
      $order_totals = $order_total_modules->process();

			for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
        $sql_data_array = array('orders_id' => $orders_id,
                            'title' => $order_totals[$i]['title'],
                            'text' => $order_totals[$i]['text'],
                            'value' => $order_totals[$i]['value'], 
                            'class' => $order_totals[$i]['code'], 
                            'sort_order' => $order_totals[$i]['sort_order']);
        tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
      }
			$cart->reset(TRUE);
      break;
    }
    case "order-state-change-notification": {
      process_order_state_change_notification($root, $data, $message_log);
      break;
    }
    case "charge-amount-notification": {
      process_charge_amount_notification($root, $data, $message_log);
      break;
    }
    case "chargeback-amount-notification": {
      process_chargeback_amount_notification($root, $data, $message_log);
      break;
    }
    case "refund-amount-notification": {
      process_refund_amount_notification($root, $data, $message_log);
      break;
    }
    case "risk-information-notification": {
      process_risk_information_notification($root, $data, $message_log);
      break;
    }
    default: {
		  $errstr = date("D M j G:i:s T Y").":- Invalid";
		  error_log($errstr, 3, API_CALLBACK_ERROR_LOG);
		  exit($errstr);
	    break;
    }
  }
	
  fclose($message_log);
  exit(0);

  function error_func($err_str, $mess_type = '3') {
    $err_str = date("D M j G:i:s T Y").":- ". $err_str. "\n";	
		error_log($err_str, $mess_type, API_CALLBACK_ERROR_LOG);
	}
	
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
  function process_merchant_calculation_callback($root, $data, $message_log) {
	  
  }
  function process_new_order_notification($root, $data, $googlepayment, $cart, $customer_id, $languages_id, $message_log) {
	  
// 1. Get cart contents
// 2. Add a row in orders table
// 3. Add a row for each product in orders_products table
// 4. Add rows if required to orders_products_attributes table
// 5. Add a row to orders_status_history and orders_total
// 6. Check stock configuration and update inventory if required

    $products = $cart->get_products(); 
//Check if buyer had logged in
    if(isset($customer_id) && $customer_id != '') {
			$cust_id = $customer_id;
      $oper="update";
			$params = ' customers_id = '.$cust_id;
    }
		else {
// Else check if buyer is a new user from Google Checkout			
		  $customer_info = tep_db_fetch_array(tep_db_query("select customers_id from " .$googlepayment->table_name  . " where buyer_id = ". $data[$root]['buyer-id']  ));   
			if($customer_info['customers_id'] == '')  {
// Add if new user				
				$sql_data_array = array('customers_gender' => '',
                          'customers_firstname' => $data[$root]['buyer-shipping-address']['contact-name'],
                          'customers_lastname' => '',
                          'customers_dob' => '',
                          'customers_email_address' => $data[$root]['buyer-shipping-address']['email'],
                          'customers_default_address_id' => 0,
                          'customers_telephone' => $data[$root]['buyer-shipping-address']['phone'],
                          'customers_fax' => $data[$root]['buyer-shipping-address']['fax'],
                          'customers_password' => $data[$root]['buyer-id'], 
                          'customers_newsletter' => ''); 
   		  tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);	
				$cust_id = tep_db_insert_id();
        $sql_data_array = array('customers_info_id' => $cust_id,
                          'customers_info_date_of_last_logon' => '',
                          'customers_info_number_of_logons' => '',
                          'customers_info_date_account_created' => '',
                          'customers_info_date_account_last_modified' => '',
                          'global_product_notifications' => ''); 
   		  tep_db_perform(TABLE_CUSTOMERS_INFO, $sql_data_array);	
 			 	$str = "insert into ". $googlepayment->table_name . " values ( " . $cust_id. ", ". $data[$root]['buyer-id']. ")";
			  tep_db_query("insert into ". $googlepayment->table_name . " values ( " . $cust_id. ", ". $data[$root]['buyer-id']. ")");	
				$oper="insert";
				$params="";
			}
			else {
   			$cust_id = $customer_info['customers_id'];		
				$oper="update";
				$params = ' customers_id = '.(int)$cust_id;
      }	
		}
// Update address book with the latest entry
// This has the disadvantage of overwriting an existing address book entry of the user
 		$str = "select zone_id from ". TABLE_ZONES . " where zone_id = '" . $data[$root]['buyer-shipping-address']['region'] . "'";
		$zone_answer = tep_db_fetch_array(tep_db_query("select zone_id, zone_country_id from ". TABLE_ZONES . " where zone_code = '" . $data[$root]['buyer-shipping-address']['region'] . "'")); 
		$sql_data_array = array('customers_id' => $cust_id,
                          'entry_gender' => '',
                          'entry_company' => $data[$root]['buyer-shipping-address']['company-name'],
                          'entry_firstname' => $data[$root]['buyer-shipping-address']['contact-name'],
                          'entry_lastname' => '',
                          'entry_street_address' => $data[$root]['buyer-shipping-address']['address1'],
													'entry_suburb' => $data[$root]['buyer-shipping-address']['address2'],
													'entry_postcode' => $data[$root]['buyer-shipping-address']['postal-code'],
													'entry_city' => $data[$root]['buyer-shipping-address']['city'],
													'entry_state' => $data[$root]['buyer-shipping-address']['region'],
													'entry_country_id' => $zone_answer['zone_country_id'],
													'entry_zone_id' => $zone_answer['zone_id']);
	  tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, $oper, $params);	
				
		if($oper == "insert") {
		  $address_book_id = tep_db_insert_id();
			tep_db_query('update '. TABLE_CUSTOMERS . ' set customers_default_address_id= '. $address_book_id . ' where customers_id = ' . $cust_id  );  	
		}
				
   	$sql_data_array = array('customers_id' => $cust_id,
                           'customers_name' => $data[$root]['buyer-shipping-address']['contact-name'],
                           'customers_company' => $data[$root]['buyer-shipping-address']['company-name'],
                           'customers_street_address' => $data[$root]['buyer-shipping-address']['address1'],
                           'customers_suburb' => $data[$root]['buyer-shipping-address']['address2'],
                           'customers_city' => $data[$root]['buyer-shipping-address']['city'],
                           'customers_postcode' => $data[$root]['buyer-shipping-address']['postal-code'], 
                           'customers_state' => $data[$root]['buyer-shipping-address']['region'],
                           'customers_country' => $data[$root]['buyer-shipping-address']['country-code'], 
                           'customers_telephone' => $data[$root]['buyer-shipping-address']['phone'], 
                           'customers_email_address' => $data[$root]['buyer-shipping-address']['email'],
                           'customers_address_format_id' => 2, 
                           'delivery_name' => $data[$root]['buyer-shipping-address']['contact-name'], 
                           'delivery_company' => $data[$root]['buyer-shipping-address']['company-name'],
                           'delivery_street_address' => $data[$root]['buyer-shipping-address']['address1'], 
                           'delivery_suburb' => $data[$root]['buyer-shipping-address']['address2'], 
                           'delivery_city' => $data[$root]['buyer-shipping-address']['city'], 
                           'delivery_postcode' => $data[$root]['buyer-shipping-address']['postal-code'], 
                           'delivery_state' => $data[$root]['buyer-shipping-address']['region'], 
                           'delivery_country' => $data[$root]['buyer-shipping-address']['country-code'], 
                           'delivery_address_format_id' => 2, 
                           'billing_name' => $data[$root]['buyer-billing-address']['contact-name'], 
                           'billing_company' => $data[$root]['buyer-billing-address']['company-name'],
                           'billing_street_address' => $data[$root]['buyer-billing-address']['address1'], 
                           'billing_suburb' => $data[$root]['buyer-billing-address']['address2'], 
                           'billing_city' => $data[$root]['buyer-billing-address']['city'], 
                           'billing_postcode' => $data[$root]['buyer-billing-address']['postal-code'], 
                           'billing_state' => $data[$root]['buyer-billing-address']['region'], 
                           'billing_country' => $data[$root]['buyer-billing-address']['country-code'], 
                           'billing_address_format_id' => 2, 
                           'payment_method' => 'Google Checkout',
                           'cc_type' => '', 
                           'cc_owner' => '', 
                           'cc_number' => '', 
                           'cc_expires' => '', 
                           'date_purchased' => $data[$root]['timestamp'], 
                           'orders_status' => 1, 
                           'currency' => "USD",
                           'currency_value' => 1);
   	tep_db_perform(TABLE_ORDERS, $sql_data_array);	
//Insert entries into orders_products	
		$orders_id = tep_db_insert_id();						
		for($i=0; $i<sizeof($products); $i++) {
			$str = "select tax_rate from ". TABLE_TAX_RATES . " as tr, ". TABLE_ZONES . " as z, ". TABLE_ZONES_TO_GEO_ZONES . " as ztgz where z.zone_code= '". $data[$root]['buyer-shipping-address']['region'] . "' and z.zone_id = ztgz.zone_id and tr.tax_zone_id=ztgz.geo_zone_id and tax_class_id= ". $products[$i]['tax_class_id'];
      $tax_answer = tep_db_fetch_array(tep_db_query("select tax_rate from ". TABLE_TAX_RATES . " as tr, ". TABLE_ZONES . " as z, ". TABLE_ZONES_TO_GEO_ZONES . " as ztgz where z.zone_code= '". $data[$root]['buyer-shipping-address']['region'] . "' and z.zone_id = ztgz.zone_id and tr.tax_zone_id=ztgz.geo_zone_id and tax_class_id= ". $products[$i]['tax_class_id']));
			$products_tax = $tax_answer['tax_rate'];
			$sql_data_array = array('orders_id' => $orders_id,
                          'products_id' => $products[$i]['id'],
                          'products_model' => $products[$i]['model'],
                          'products_name' => $products[$i]['name'],
                          'products_price' => $products[$i]['price'],
                          'final_price' => $products[$i]['final_price'],
                          'products_tax' => $products_tax, 
                          'products_quantity' => $products[$i]['quantity'] ); 
   		tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);	
//Insert entries into orders_products_attributes								
     	$orders_products_id = tep_db_insert_id();
			if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes']))  {
        while (list($option, $value) = each($products[$i]['attributes'])) {
			    $attributes = tep_db_fetch_array(tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $products[$i]['id'] . "'
                                        and pa.options_id = '" . $option . "'
                                        and pa.options_id = popt.products_options_id
                                        and pa.options_values_id = '" . $value . "'
                                        and pa.options_values_id = poval.products_options_values_id
                                        and popt.language_id = '" . $languages_id . "'
                                        and poval.language_id = '" . $languages_id . "'"));
					$sql_data_array = array('orders_id' => $orders_id,
                          'orders_products_id' => $orders_products_id,
                          'products_options' => $attributes['products_options_name'],
                          'products_options_values' => $attributes['products_options_values_name'],
                          'options_values_price' => $attributes['options_values_price'],
                          'price_prefix' => $attributes['price_prefix']); 
		   		tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);	
        }		
			}		
		}
//Insert entry into orders_status_history		
    $sql_data_array = array('orders_id' => $orders_id,
                           'orders_status_id' => 1,
		                       'date_added' => $data[$root]['timestamp'],
                           'customer_notified' => 1,
                           'comments' => ''); 
		tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);	
    //send_ack(); 
		return $orders_id;
  }
 
	function process_order_state_change_notification($root, $data, $message_log) {
    $new_financial_state = $data[$root]['new-financial-order-state'];
	  $new_fulfillment_order = $data[$root]['new_fulfillment_order'];
	
	  fwrite($log_file,sprintf("\n%s\n", $data[$root]['new-financial-order-state']));
	  fwrite($log_file, sprintf("\r\n%s\n",$request_url));
	
	  switch($new_financial_state) {
	    case 'REVIEWING': {
	      break;
	    }
	    case 'CHARGEABLE': {
		    break;
	    }
	    case 'CHARGING': {
	      break;
	    }
	    case 'CHARGED': {
		    break;
	    }
	    case 'PAYMENT-DECLINED': {
	      break;
	    }
	    case 'CANCELLED': {
	      break;
	    }
	    case 'CANCELLED_BY_GOOGLE': {
	      break;
	    }
	    default:
	      break;	
	    } 
	
	  switch($new_fulfillment_order) {
	    case 'NEW': {
	      break;
	    }	
	    case 'PROCESSING': {
	      break;	  
	    }
	    case 'DELIVERED': {
	      break;	  
	    }
	    case 'WILL_NOT_DELIVER': {
	      break;
	    }
	    default:
	      break;
	  }
		send_ack();	  
  }
  
	function process_charge_amount_notification($root, $data, $message_log) {
    send_ack(); 	  
  }
  function process_chargeback_amount_notification($root, $data, $message_log) {
	}
  function process_refund_amount_notification($root, $data, $message_log) {
	  
  }
  function process_risk_information_notification($root, $data, $message_log) {
    send_ack();	  
  }
  
  function send_req($url, $merid, $merkey, $postargs, $message_log) {
    //error_reporting(E_ALL);
    // Get the curl session object
    $session = curl_init($url);
	
	  $header_string_1 = "Authorization: Basic ".base64_encode($merid.':'.$merkey);
	  $header_string_2 = "Content-Type: application/xml";
	  $header_string_3 = "Accept: application/xml";
	
	  fwrite($log_file, sprintf("\r\n%s %s %s\n",$header_string_1, $header_string_2, $header_string_3));
    // Set the POST options.
    curl_setopt ($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_HTTPHEADER, array($header_string_1, $header_string_2, $header_string_3));
    curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    // Do the POST and then close the session
    $response = curl_exec($session);
    curl_close($session);

	  fwrite($log_file, sprintf("\r\n%s\n",$response));
	
    // Get HTTP Status code from the response
    $status_code = array();
    preg_match('/\d\d\d/', $response, $status_code);

    // Check for errors
    switch( $status_code[0] ) {
	    case 200:
		  // Success
	      break;
	    case 503:
	      die('Error 503: Service unavailable. An internal problem prevented us from returning data to you.');
	      break;
	    case 403:
        die('Error 403: Forbidden. You do not have permission to access this resource, or are over your rate limit.');
	      break;
	    case 400:
	      die('Error 400:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML response.');
	      break;
	    default:
		    die('Error :' . $status_code[0]);
    }
   	
	  fwrite($log_file, sprintf("\r\n%s\n",$status_code[0]));
  }

  function send_ack($schema_url) {
    $acknowledgment = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
    "<notification-acknowledgment xmlns=\"" . 
    $schema_url . "\"/>";
    echo $acknowledgment;
	}
  // ** END GOOGLE CHECKOUT **
?>
