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

// error_reporting(0);

  chdir('./..');
  $curr_dir = getcwd();
  define('API_CALLBACK_MESSAGE_LOG', $curr_dir."/googlecheckout/response_message.log");
  define('API_CALLBACK_ERROR_LOG', $curr_dir."/googlecheckout/response_error.log");

  if(check_file('includes/modules/payment/googlecheckout.php'))
    include_once('includes/modules/payment/googlecheckout.php');

  require_once($curr_dir.'/googlecheckout/googlemerchantcalculations.php');
  require_once($curr_dir.'/googlecheckout/googleresult.php');


  
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
  fwrite($message_log, sprintf("\n\rHTTP_USER_AGENT:- %s\n",getenv('HTTP_USER_AGENT')));
  
  $xmlp = new XmlParser($xml_response);
  $root = $xmlp->getRoot();
  $data = $xmlp->getData();
  fwrite($message_log, sprintf("\n\r%s:- %s\n",date("D M j G:i:s T Y"), $root));	  

	// restore session
	
	//print_r($data);
  if(isset($data[$root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'])) {
    $private_data = $data[$root]['shopping-cart']['merchant-private-data']['session-data']['VALUE'];
    $sess_id = substr($private_data, 0 , strpos($private_data,";"));
    $sess_name = substr($private_data, strpos($private_data,";")+1);
    fwrite($message_log, sprintf("\r\n%s :- %s, %s\n",date("D M j G:i:s T Y"), $sess_id, $sess_name));						
    //If session management is supported by this PHP version
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
  if(isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) && isset($HTTP_SERVER_VARS['PHP_AUTH_PW'])) {
    $compare_mer_id = $HTTP_SERVER_VARS['PHP_AUTH_USER']; 
    $compare_mer_key = $HTTP_SERVER_VARS['PHP_AUTH_PW'];
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
    	
     		
      $orders_id = process_new_order_notification($root, $data, $googlepayment, $cart, $customer_id, $languages_id, $message_log);
	  	$cart->reset(TRUE);
// Add the order details to the table
// This table could be modified to hold the merchant id and key if required 
// so that different mids and mkeys can be used for different orders
      tep_db_query("insert into " . $googlepayment->table_order . " values (" . $orders_id . ", ". makeSqlString($data[$root]['google-order-number']['VALUE']) . ", " . makeSqlFloat($data[$root]['order-total']['VALUE']) . ")");
			if(is_array($data[$root]['order-adjustment']['shipping']))
      foreach($data[$root]['order-adjustment']['shipping'] as $ship); {
        $shipping =  $ship['shipping-name']['VALUE'];
        $ship_cost = $ship['shipping-cost']['VALUE']; 
      }
      $tax_amt = $data[$root]['order-adjustment']['total-tax']['VALUE'];
      $order_total = $data[$root]['order-total']['VALUE'];
 
      require(DIR_WS_CLASSES . 'order.php');
      $order = new order();	    
// load the selected shipping module
      require(DIR_WS_CLASSES . 'shipping.php');
      $shipping_modules = new shipping($shipping);
        
      require_once(DIR_WS_LANGUAGES . $language . '/modules/payment/googlecheckout.php');

// Update values so that order_total modules get the correct values 			
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
        $sql_data_array = array('orders_id' => makeSqlInteger($orders_id),
                                'title' => makeSqlString($order_totals[$i]['title']),
                                'text' => makeSqlString($order_totals[$i]['text']),
                                'value' => makeSqlString($order_totals[$i]['value']), 
                                'class' => makeSqlString($order_totals[$i]['code']), 
                                'sort_order' => makeSqlInteger($order_totals[$i]['sort_order']));
        tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
      }
      break;
    }
    
    case "order-state-change-notification": {
      process_order_state_change_notification($root, $data, $message_log, $googlepayment);
      break;
    }
    case "charge-amount-notification": {
      process_charge_amount_notification($root, $data, $message_log, $googlepayment);
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
      process_risk_information_notification($root, $data, $message_log, $googlepayment);
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
	global $cart, $googlepayment, $order;

	// i get all the enabled shipping methods  
	require(DIR_WS_CLASSES . 'shipping.php');
	$shipping_modules = new shipping;
	// i get an hash-array with the description of each shipping method 
	$methods_hash = $googlepayment->getMethods();
	require(DIR_WS_CLASSES . 'order.php');
	$order = new order;
	 
	// register a random ID in the session to check throughout the checkout procedure
	// against alterations in the shopping cart contents
	if (!tep_session_is_registered('cartID')) tep_session_register('cartID');
		$cartID = $cart->cartID;
  	
		$total_weight = $cart->show_weight();
		$total_count = $cart->count_contents();
  		
 		
        // Create the results and send it
		$merchant_calc = new GoogleMerchantCalculations();
      // Loop through the list of address ids from the callback
		$addresses = get_arr_result($data[$root]['calculate']['addresses']['anonymous-address']);
  
  		// required for some shipping methods (ie. USPS)
		require_once('includes/classes/http_client.php');
		foreach($addresses as $curr_address) {
	      	// set up the order address
	        $curr_id = $curr_address['id'];
	        $country = $curr_address['country-code']['VALUE'];
	        $city = $curr_address['city']['VALUE'];
	        $region = $curr_address['region']['VALUE'];
	        $postal_code = $curr_address['postal-code']['VALUE'];
	
			$countr_query = tep_db_query("select * 
		                               from " . TABLE_COUNTRIES . " 
		                               where countries_iso_code_2 = '" . makeSqlString($country) ."'");
	
	
			$row = tep_db_fetch_array($countr_query);
		 	$order->delivery['country'] = array('id' => $row['countries_id'], 
												'title' => $row['countries_name'], 
												'iso_code_2' => $country, 
												'iso_code_3' => $row['countries_iso_code_3']);
	
			$order->delivery['country_id'] = $row['countries_id'];
			$order->delivery['format_id'] = $row['address_format_id'];
			
			$zone_query = tep_db_query("select * 
		                               from " . TABLE_ZONES . "
		                               where zone_code = '" . makeSqlString($region) ."'");
	
			$row = tep_db_fetch_array($zone_query);
			$order->delivery['zone_id'] = $row['zone_id'];
			$order->delivery['state'] = $row['zone_name'];
	
			$order->delivery['city'] = $city;
			$order->delivery['postcode'] = $postal_code;
			//print_r($order);
	
	        // Loop through each shipping method if merchant-calculated shipping
	        // support is to be provided
			if(isset($data[$root]['calculate']['shipping'])) {
		        $shipping = get_arr_result($data[$root]['calculate']['shipping']['method']);
		        foreach($shipping as $curr_ship) {
		         	$name = $curr_ship['name'];
		            
		            //Compute the price for this shipping method and address id
		        
			        list($a, $method_name) = explode(': ',$name);
		   
			        //print_r($order);
		            $quotes = $shipping_modules->quote($methods_hash[$method_name][0], $methods_hash[$method_name][2]);
		            //print_r($quotes);
		            $price = $quotes[0]['methods'][0]['cost'];
		            $shippable = "true";
		            //if there is a problem with the method, i mark it as non-shippable
		            if(!isset($quotes[0]['methods'][0]['cost']) || isset($quotes[0]['error'])) {
		            	$shippable = "false";
		            	$price = "0";
		            }
		            
		            $merchant_result = new GoogleResult($curr_id);
		            $merchant_result->SetShippingDetails($name, $price, "USD", $shippable);
		
		            if($data[$root]['calculate']['tax']['VALUE'] == "true") {
		              //Compute tax for this address id and shipping type
		              $amount = 15; // Modify this to the actual tax value
		              $merchant_result->SetTaxDetails($amount, "USD");
		            }
		
		            $codes = get_arr_result($data[$root]['calculate']['merchant-code-strings']['merchant-code-string']);
		            foreach($codes as $curr_code) {
		              //Update this data as required to set whether the coupon is valid, the code and the amount
		              $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "USD", "test2");
		              $merchant_result->AddCoupons($coupons);
		            }
		            $merchant_calc->AddResult($merchant_result);
		          }
	        } else {
		          $merchant_result = new GoogleResult($curr_id);
		          if($data[$root]['calculate']['tax']['VALUE'] == "true") {
		            //Compute tax for this address id and shipping type
		            $amount = 15; // Modify this to the actual tax value
		            $merchant_result->SetTaxDetails($amount, "USD");
		          }
		          $codes = get_arr_result($data[$root]['calculate']['merchant-code-strings']
		              ['merchant-code-string']);
		          foreach($codes as $curr_code) {
		            //Update this data as required to set whether the coupon is valid, the code and the amount
		            $coupons = new GoogleCoupons("true", $curr_code['code'], 5, "USD", "test2");
		            $merchant_result->AddCoupons($coupons);
		          }
		          $merchant_calc->AddResult($merchant_result);
	        }
      }
      echo $merchant_calc->getXML();
      //$response->ProcessMerchantCalculations($merchant_calc);
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
    } else {
// Else check if buyer is a new user from Google Checkout			
      $customer_info = tep_db_fetch_array(tep_db_query("select customers_id from " .$googlepayment->table_name  . " where buyer_id = ". makeSqlString($data[$root]['buyer-id']['VALUE'])  ));   
      if($customer_info['customers_id'] == '')  {
// Add if new user
	$sql_data_array = array('customers_gender' => '',
                          'customers_firstname' => makeSqlString($data[$root]['buyer-shipping-address']['contact-name']['VALUE']),
                          'customers_lastname' => '',
                          'customers_dob' => '',
                          'customers_email_address' => makeSqlString($data[$root]['buyer-shipping-address']['email']['VALUE']),
                          'customers_default_address_id' => 0,
							// using billing address phone because GC doesn't return phone number in the buyer-shipping-address.
                          'customers_telephone' => makeSqlString($data[$root]['buyer-billing-address']['phone']['VALUE']),
                          'customers_fax' => makeSqlString($data[$root]['buyer-shipping-address']['fax']['VALUE']),
                          'customers_password' => makeSqlString($data[$root]['buyer-id']['VALUE']), 
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
        $str = "insert into ". $googlepayment->table_name . " values ( " . $cust_id. ", ". $data[$root]['buyer-id']['VALUE']. ")";
        tep_db_query("insert into ". $googlepayment->table_name . " values ( " . $cust_id. ", ". $data[$root]['buyer-id']['VALUE']. ")");	
        $oper="insert";
        $params="";
     } else {
       $cust_id = $customer_info['customers_id'];		
       $oper="update";
       $params = ' customers_id = '.(int)$cust_id;
     }	
   }
// Update address book with the latest entry
// This has the disadvantage of overwriting an existing address book entry of the user
    $str = "select zone_id from ". TABLE_ZONES . " where zone_id = '" . makeSqlString($data[$root]['buyer-shipping-address']['region']['VALUE']) . "'";
    $zone_answer = tep_db_fetch_array(tep_db_query("select zone_id, zone_country_id from ". TABLE_ZONES . " where zone_code = '" . $data[$root]['buyer-shipping-address']['region']['VALUE'] . "'")); 
		
    $sql_data_array = array('customers_id' => $cust_id,
                          'entry_gender' => '',
                          'entry_company' => makeSqlString($data[$root]['buyer-shipping-address']['company-name']['VALUE']),
                          'entry_firstname' => makeSqlString($data[$root]['buyer-shipping-address']['contact-name']['VALUE']),
                          'entry_lastname' => '',
                          'entry_street_address' => makeSqlString($data[$root]['buyer-shipping-address']['address1']['VALUE']),
      	                  'entry_suburb' => makeSqlString($data[$root]['buyer-shipping-address']['address2']['VALUE']),
                          'entry_postcode' => makeSqlString($data[$root]['buyer-shipping-address']['postal-code']['VALUE']),
                          'entry_city' => makeSqlString($data[$root]['buyer-shipping-address']['city']['VALUE']),
                          'entry_state' => makeSqlString($data[$root]['buyer-shipping-address']['region']['VALUE']),
                          'entry_country_id' => makeSqlInteger($zone_answer['zone_country_id']['VALUE']),
                          'entry_zone_id' => makeSqlInteger($zone_answer['zone_id']['VALUE']));
    tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, $oper, $params);	
				
    if($oper == "insert") {
      $address_book_id = tep_db_insert_id();
      tep_db_query('update '. TABLE_CUSTOMERS . ' set customers_default_address_id= '. $address_book_id . ' where customers_id = ' . $cust_id  );  	
    }
		
    $sql_data_array = array('customers_id' => $cust_id,
                           'customers_name' => $data[$root]['buyer-shipping-address']['contact-name']['VALUE'],
                           'customers_company' => $data[$root]['buyer-shipping-address']['company-name']['VALUE'],
                           'customers_street_address' => $data[$root]['buyer-shipping-address']['address1']['VALUE'],
                           'customers_suburb' => $data[$root]['buyer-shipping-address']['address2']['VALUE'],
                           'customers_city' => $data[$root]['buyer-shipping-address']['city']['VALUE'],
                           'customers_postcode' => $data[$root]['buyer-shipping-address']['postal-code']['VALUE'], 
                           'customers_state' => $data[$root]['buyer-shipping-address']['region']['VALUE'],
                           'customers_country' => $data[$root]['buyer-shipping-address']['country-code']['VALUE'],
                           // using billing address phone because GC doesn't return phone number in the buyer-shipping-address. 
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
                           'billing_name' => makeSqlString($data[$root]['buyer-billing-address']['contact-name']['VALUE']), 
                           'billing_company' => makeSqlString($data[$root]['buyer-billing-address']['company-name']['VALUE']),
                           'billing_street_address' => makeSqlString($data[$root]['buyer-billing-address']['address1']['VALUE']), 
                           'billing_suburb' => makeSqlString($data[$root]['buyer-billing-address']['address2']['VALUE']), 
                           'billing_city' => makeSqlString($data[$root]['buyer-billing-address']['city']['VALUE']), 
                           'billing_postcode' => makeSqlString($data[$root]['buyer-billing-address']['postal-code']['VALUE']), 
                           'billing_state' => makeSqlString($data[$root]['buyer-billing-address']['region']['VALUE']), 
                           'billing_country' => makeSqlString($data[$root]['buyer-billing-address']['country-code']['VALUE']), 
                           'billing_address_format_id' => 2, 
                           'payment_method' => 'Google Checkout',
                           'cc_type' => '', 
                           'cc_owner' => '', 
                           'cc_number' => '', 
                           'cc_expires' => '', 
                           'date_purchased' => makeSqlString($data[$root]['timestamp']['VALUE']), 
                           'orders_status' => 1, 
                           'currency' => "USD",
                           'currency_value' => 1);
    tep_db_perform(TABLE_ORDERS, $sql_data_array);	
//Insert entries into orders_products	
    $orders_id = tep_db_insert_id();						
    for($i=0; $i<sizeof($products); $i++) {
      $str = "select tax_rate from ". TABLE_TAX_RATES . " as tr, ". TABLE_ZONES . " as z, ". TABLE_ZONES_TO_GEO_ZONES . " as ztgz where z.zone_code= '". $data[$root]['buyer-shipping-address']['region']['VALUE'] . "' and z.zone_id = ztgz.zone_id and tr.tax_zone_id=ztgz.geo_zone_id and tax_class_id= ". $products[$i]['tax_class_id'];
      $tax_answer = tep_db_fetch_array(tep_db_query("select tax_rate from ". TABLE_TAX_RATES . " as tr, ". TABLE_ZONES . " as z, ". TABLE_ZONES_TO_GEO_ZONES . " as ztgz where z.zone_code= '". $data[$root]['buyer-shipping-address']['region']['VALUE'] . "' and z.zone_id = ztgz.zone_id and tr.tax_zone_id=ztgz.geo_zone_id and tax_class_id= ". $products[$i]['tax_class_id']));
      $products_tax = $tax_answer['tax_rate'];
      
      $sql_data_array = array('orders_id' => $orders_id,
                          'products_id' => makeSqlInteger($products[$i]['id']),
                          'products_model' => makeSqlString($products[$i]['model']),
                          'products_name' => makeSqlString($products[$i]['name']),
                          'products_price' => makeSqlFloat($products[$i]['price']),
                          'final_price' => makeSqlFloat($products[$i]['final_price']),
                          'products_tax' => makeSqlFloat($products_tax), 
                          'products_quantity' => makeSqlInteger($products[$i]['quantity'])); 
      tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);	
//Insert entries into orders_products_attributes								
      $orders_products_id = tep_db_insert_id();
      if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes']))  {
        while (list($option, $value) = each($products[$i]['attributes'])) {
          $attributes = tep_db_fetch_array(tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $products[$i]['id'] . "'
                                        and pa.options_id = '" . makeSqlString($option) . "'
                                        and pa.options_id = popt.products_options_id
                                        and pa.options_values_id = '" . makeSqlString($value) . "'
                                        and pa.options_values_id = poval.products_options_values_id
                                        and popt.language_id = '" . $languages_id . "'
                                        and poval.language_id = '" . $languages_id . "'"));
                                        
          $sql_data_array = array('orders_id' => $orders_id,
                          'orders_products_id' => $orders_products_id,
                          'products_options' => makeSqlString($attributes['products_options_name']),
                          'products_options_values' => makeSqlString($attributes['products_options_values_name']),
                          'options_values_price' => makeSqlFloat($attributes['options_values_price']),
                          'price_prefix' => makeSqlString($attributes['price_prefix'])); 
          tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);	
        }		
      }		
            
	    // update inventory -- add by rancidtoys
	    // 
	    // must review!
	    //
//	    print_r($products);
		$stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . $products[$i]['id'] . "'");
		//echo "select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . $products[$i]['id'] . "'" . tep_db_num_rows($stock_query);
		if (tep_db_num_rows($stock_query) > 0) {
			$stock_values = tep_db_fetch_array($stock_query);
			$stock_left = $stock_values['products_quantity'] - $products[$i]['quantity'];
			tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . $products[$i]['id'] . "'");
			if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
				tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . $products[$i]['id'] . "'");
			}
		}
		// end add by rancidtoys
    }
    
//Insert entry into orders_status_history		
    $sql_data_array = array('orders_id' => $orders_id,
                           'orders_status_id' => 1,
                           'date_added' => 'now()',
                           'customer_notified' => 1,
                           'comments' => 'Google Checkout Order No: ' . $data[$root]['google-order-number']['VALUE']."\n" .
                           		'Merachnat Calculations: '. $data[$root]['order-adjustment']['merchant-calculation-successful']['VALUE']);  //Add Order number to Comments box. For customer's reference. 
    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);	
    send_ack();
    return $orders_id;
  }
function process_order_state_change_notification($root, $data, $message_log, $googlepayment) {
    $new_financial_state = $data[$root]['new-financial-order-state']['VALUE'];
    $new_fulfillment_order = $data[$root]['new-fulfillment-order-state']['VALUE'];

    $previous_financial_state = $data[$root]['previous-financial-order-state']['VALUE'];
    $previous_fulfillment_order = $data[$root]['previous-fulfillment-order-state']['VALUE'];

    $google_order_number = $data[$root]['google-order-number']['VALUE'];
    
    $google_orders = tep_db_query("SELECT orders_id from " . $googlepayment->table_order . " " .
    														"where google_order_number = '". makeSqlString($google_order_number) ."'");
		$google_order = tep_db_fetch_array($google_orders);
		
    fwrite($message_log,sprintf("\n%s\n", $data[$root]['new-financial-order-state']['VALUE']));

		$update = false;
		if($previous_financial_state != $new_financial_state)
    switch($new_financial_state) {
      case 'REVIEWING': {
        break;
      }
      case 'CHARGEABLE': {
				$update = true;
				$orders_status_id = 1;
				$comments = 'Time: ' . $data[$root]['timestamp']['VALUE']. "\n".'New state: '. $new_financial_state."\n".'Order ready to be charged!'; 
				$customer_notified = 0;
        break;
      }
      case 'CHARGING': {
        break;
      }
      case 'CHARGED': {
				$update = true;
				$orders_status_id = 2;
				$comments = 'Time: ' . $data[$root]['timestamp']['VALUE']. "\n".'New state: '. $new_financial_state ;
				$customer_notified = 0;
        break;
      }

      case 'PAYMENT-DECLINED': {
				$update = true;
				$orders_status_id = 1;
				$customer_notified = 1;
				$comments = 'Time: ' . $data[$root]['timestamp']['VALUE']. "\n".'New state: '. $new_financial_state .'Payment was declined!'; 
        break;
      }
      case 'CANCELLED': {
				$update = true;
				$orders_status_id = 1;
				$customer_notified = 1;
				$comments = 'Time: ' . $data[$root]['timestamp']['VALUE']. "\n".'New state: '. $new_financial_state ."\n".'Order was canceled.'."\n".'Reason:'. $data[$root]['reason']['VALUE']; 
        break;
      }
      case 'CANCELLED_BY_GOOGLE': {
				$update = true;
				$orders_status_id = 1;
				$customer_notified = 1;
				$comments = 'Time: ' . $data[$root]['timestamp']['VALUE']. "\n".'New state: '. $new_financial_state ."\n".'Order was canceled by Google.'."\n".'Reason:'. $data[$root]['reason']['VALUE']; 
        break;
      }
      default:
        break;
    }
    
    if($update) {
	    $sql_data_array = array('orders_id' => $google_order['orders_id'],
	                           'orders_status_id' => $orders_status_id,
	                           'date_added' => 'now()',
	                           'customer_notified' => $customer_notified,
	                           'comments' => $comments);
      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      tep_db_query("UPDATE " . TABLE_ORDERS . "SET orders_status = '".$orders_status_id."' WHERE orders_id = '".makeSqlInteger($google_order['orders_id'])."'");
    }
    
 		$update = false;   
 		if($previous_fulfillment_order != $new_fulfillment_order)
    switch($new_fulfillment_order) {
      case 'NEW': {
        break;
      }
      case 'PROCESSING': {
        break;
      }
      case 'DELIVERED': {
				$update = true;
				$orders_status_id = 3;
				$comments = 'Time: ' . $data[$root]['timestamp']['VALUE']. "\n".'New state: '. $new_fulfillment_order ."\n".'Order was Delivered.'."\n";
				$customer_notified = 1;
        break;
      }
      case 'WILL_NOT_DELIVER': {
        break;
      }
      default:
         break;
    }

    if($update) {
	    $sql_data_array = array('orders_id' => $google_order['orders_id'],
	                           'orders_status_id' => $orders_status_id,
	                           'date_added' => 'now()',
	                           'customer_notified' => $customer_notified,
	                           'comments' => $comments);
      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
      tep_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status = '".$orders_status_id."' WHERE orders_id = '".makeSqlInteger($google_order['orders_id'])."'");
    }

    send_ack();	  
  }  
  function process_charge_amount_notification($root, $data, $message_log, $googlepayment) {
    $google_order_number = $data[$root]['google-order-number']['VALUE'];
    $google_orders = tep_db_query("SELECT orders_id from " . $googlepayment->table_order . " " .
    														"where google_order_number = '". makeSqlString($google_order_number) ."'");
		$google_order = tep_db_fetch_array($google_orders);
		
//   	fwrite($message_log,sprintf("\n%s\n", $google_order['orders_id'],));
  	 
  	
    $sql_data_array = array('orders_id' => $google_order['orders_id'],
                           'orders_status_id' => 2,
                           'date_added' => 'now()',
                           'customer_notified' => 0,
                           'comments' => 'Latest charge amount: ' .$data[$root]['latest-charge-amount']['currency'].' ' .$data[$root]['latest-charge-amount']['VALUE']."\n". 'Total charge amount: ' .$data[$root]['latest-charge-amount']['currency'].' ' . $data[$root]['total-charge-amount']['VALUE']);  	
    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    tep_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status = '". 2 ."' WHERE orders_id = '".makeSqlInteger($google_order['orders_id'])."'");
    send_ack();
  }
  function process_chargeback_amount_notification($root, $data, $message_log) {
    send_ack(); 
  }
  function process_refund_amount_notification($root, $data, $message_log) {
    send_ack(); 
  }
  function process_risk_information_notification($root, $data, $message_log, $googlepayment) {
    $google_order_number = $data[$root]['google-order-number']['VALUE'];
    $google_orders = tep_db_query("SELECT orders_id from " . $googlepayment->table_order . " " .
    														"where google_order_number = '". makeSqlString($google_order_number) ."'");
		$google_order = tep_db_fetch_array($google_orders);
		
//   fwrite($message_log,sprintf("\n%s\n", $google_order->fields['orders_id']));
  	 
  	
    $sql_data_array = array('orders_id' => $google_order['orders_id'],
                           'orders_status_id' => 1,
                           'date_added' => 'now()',
                           'customer_notified' => 0,
                           'comments' => 'Risk Information: ' ."\n" .
                            							' Elegible for Protection: '.$data[$root]['risk-information']['eligible-for-protection']['VALUE']."\n" .
                           								' Avs Response: '.$data[$root]['risk-information']['avs-response']['VALUE']."\n" .
																					' Cvn Response: '.$data[$root]['risk-information']['cvn-response']['VALUE']."\n" .
																					' Partial CC number: '.$data[$root]['risk-information']['partial-cc-number']['VALUE']."\n" .
																					' Buyer account age: '.$data[$root]['risk-information']['buyer-account-age']['VALUE']."\n" .
																					' IP Address: '.$data[$root]['risk-information']['ip-address']['VALUE']."\n" 
                           								);  	
    tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
    tep_db_query("UPDATE " . TABLE_ORDERS . " SET orders_status_id = '". 1 ."' WHERE orders_id = '".makeSqlInteger($google_order['orders_id'])."'");
    send_ack();
  }

  
  function send_ack() {
    $acknowledgment = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" .
    "<notification-acknowledgment xmlns=\"http://checkout.google.com/schema/2\"/>";
    echo $acknowledgment;
  }

  //Functions to prevent SQL injection attacks
  function makeSqlString($str) {
    return addcslashes(stripcslashes($str), "'\"\\\0..\37!@\@\177..\377");
  }

  function makeSqlInteger($val) {
    return ((settype($val, 'integer'))?($val):0); 
  }

  function makeSqlFloat($val) {
    return ((settype($val, 'float'))?($val):0); 
  }
    /* In case the XML API contains multiple open tags
     with the same value, then invoke this function and
     perform a foreach on the resultant array.
     This takes care of cases when there is only one unique tag
     or multiple tags.
     Examples of this are "anonymous-address", "merchant-code-string"
     from the merchant-calculations-callback API
  */
  function get_arr_result($child_node) {
    $result = array();
    if(isset($child_node)) {
      if(is_associative_array($child_node)) {
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

  /* Returns true if a given variable represents an associative array */
  function is_associative_array( $var ) {
    return is_array( $var ) && !is_numeric( implode( '', array_keys( $var ) ) );
  } 
  // ** END GOOGLE CHECKOUT **
?>