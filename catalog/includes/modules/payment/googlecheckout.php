<?php
/*
  Copyright (C) 2007 Google Inc.

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


/* GOOGLE CHECKOUT
 * Class provided in modules dir to add googlecheckout as a payment option
 * Member variables refer to currently set parameter values from the database
 */

class googlecheckout {
  var $code, $title, $description, $merchantid, $merchantkey, $mode, $enabled, $shipping_support, $variant;
  var $schema_url, $base_url, $checkout_url, $checkout_diagnose_url, $request_url, $request_diagnose_url;
  var $table_name = "google_checkout", $table_order = "google_orders";
  var $ship_flat_ui, $hash;

  // Class constructor
  function googlecheckout() {
    global $order;
    global $language;
    
    require_once(DIR_FS_CATALOG .'/includes/languages/'. $language .'/modules/payment/googlecheckout.php');
    
    $this->code = 'googlecheckout';
    $this->title = MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER;
    $this->mode = MODULE_PAYMENT_GOOGLECHECKOUT_STATUS;
    $this->merchantid = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID);
    $this->merchantkey = trim(MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY);
    $this->mode = MODULE_PAYMENT_GOOGLECHECKOUT_MODE;
    $this->enabled = (MODULE_PAYMENT_GOOGLECHECKOUT_STATUS == 'True') ? true : false;
    $this->continue_url = MODULE_PAYMENT_GOOGLECHECKOUT_CONTINUE_URL;
			
    // These are the flat shipping methods, add any other that is not merchant calculated 
    $this->shipping_support = array("flat", "item", "table");

    $this->shipping_display = array(GOOGLECHECKOUT_FLAT_RATE_SHIPPING, GOOGLECHECKOUT_ITEM_RATE_SHIPPING, GOOGLECHECKOUT_TABLE_RATE_SHIPPING);

 	  // These are all the available methods for each shipping provider, 
    // see that you must set flat methods too!}
    // CONSTRAINT: Method's names MUST be UNIQUE
	// Script to create new shipping methods
	// http://demo.globant.com/~brovagnati/tools -> Shipping Method Generator
  $this->mc_shipping_methods = array(
                        'usps' => array(
                                    'domestic_types' =>
                                      array(
                                          'Express' => 'Express Mail',
                                          'First Class' => 'First-Class Mail',
                                          'Priority' => 'Priority Mail',
                                          'Parcel' => 'Parcel Post'
                                           ),

                                    'international_types' =>
                                      array(
                                          'GXG Document' => 'Global Express Guaranteed Document Service',
                                          'GXG Non-Document' => 'Global Express Guaranteed Non-Document Service',
                                          'Express' => 'Global Express Mail (EMS)',
                                          'Priority Lg' => 'Global Priority Mail - Flat-rate Envelope (large)',
                                          'Priority Sm' => 'Global Priority Mail - Flat-rate Envelope (small)',
                                          'Priority Var' => 'Global Priority Mail - Variable Weight Envelope (single)',
                                          'Airmail Letter' => 'Airmail Letter Post',
                                          'Airmail Parcel' => 'Airmail Parcel Post',
                                          'Surface Letter' => 'Economy (Surface) Letter Post',
                                          'Surface Post' => 'Economy (Surface) Parcel Post'
                                           ),
                                        ),
                        'fedex1' => array(
                                    'domestic_types' =>
                                      array(
                                          '01' => 'Priority (by 10:30AM, later for rural)',
                                          '03' => '2 Day Air',
                                          '05' => 'Standard Overnight (by 3PM, later for rural)',
                                          '06' => 'First Overnight',
                                          '20' => 'Express Saver (3 Day)',
                                          '90' => 'Home Delivery',
                                          '92' => 'Ground Service'
                                           ),

                                    'international_types' =>
                                      array(
                                          '01' => 'International Priority (1-3 Days)',
                                          '03' => 'International Economy (4-5 Days)',
                                          '06' => 'International First',
                                          '90' => 'International Home Delivery',
                                          '92' => 'International Ground Service'
                                           ),
                                        ),
                        'upsxml' => array(
                                    'domestic_types' =>
                                      array(
                                          'UPS Ground' => 'UPS Ground, ',
                                          'UPS 3 Day Select' => 'UPS 3 Day Select, ',
                                          'UPS 2nd Day Air A.M.' => 'UPS 2nd Day Air A.M., ',
                                          'UPS 2nd Day Air' => 'UPS 2nd Day Air, ',
                                          'UPS Next Day Air Saver' => 'UPS Next Day Air Saver, ',
                                          'UPS Next Day Air Early A.M.' => 'UPS Next Day Air Early A.M., ',
                                          'UPS Next Day Air' => 'UPS Next Day Air, '
                                           ),

                                    'international_types' =>
                                      array(
                                          'UPS Worldwide Expedited' => 'UPS Worldwide Expedited, 2007-04-18',
                                          'UPS Saver' => 'UPS Saver'
                                           ),
                                        ),
                        'zones' => array(
                                    'domestic_types' =>
                                      array(
                                          'zones' => 'Zones Rates'
                                           ),

                                    'international_types' =>
                                      array(
                                          'zones' => 'Zones Rates intl'
                                           ),
                                        ),
                        'flat' => array(
                                    'domestic_types' =>
                                      array(
                                          'flat' => 'Flat Rate Per Order'
                                           ),

                                    'international_types' =>
                                      array(
                                          'flat' => 'Flat Rate Per Order intl'
                                           ),
                                        ),
                        'item' => array(
                                    'domestic_types' =>
                                      array(
                                          'item' => 'Flat Rate Per Item'
                                           ),

                                    'international_types' =>
                                      array(
                                          'item' => 'Flat Rate Per Item intl'
                                           ),
                                        ),
                        'table' => array(
                                    'domestic_types' =>
                                      array(
                                          'table' => 'Table'
                                           ),

                                    'international_types' =>
                                      array(
                                          'table' => 'Table intl'
                                           ),
                                        ),
                                  );

  $this->mc_shipping_methods_names = array(
                                         'usps' => 'USPS',
                                         'fedex1' => 'FedEx',
                                         'upsxml' => 'Ups',
                                         'zones' => 'Zones',
                                         'flat' => 'Flat Rate',
                                         'item' => 'Item',
                                         'table' => 'Table',
                                        );

    $this->hash = NULL;
    $this->ship_flat_ui = 'Standard flat-rate shipping';
    $this->schema_url = 'http://checkout.google.com/schema/2';
    $this->base_url = $this->mode .'cws/v2/Merchant/'. $this->merchantid;
    $this->checkout_url = $this->base_url .'/checkout';
    $this->checkout_diagnose_url = $this->base_url .'/checkout/diagnose';
    $this->request_url = $this->base_url .'/request';
    $this->request_diagnose_url = $this->base_url .'/request/diagnose';
    $this->variant = 'text';

    if ((int)MODULE_PAYMENT_GOOGLECHECKOUT_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_GOOGLECHECKOUT_ORDER_STATUS_ID;
    }
  }

  function getMethods() {
  	if($this->hash == NULL) {
      $rta = array();
  		$this->_gethash($this->mc_shipping_methods, $rta);
  		$this->hash = $rta;
  	}
    return $this->hash;
  }

  function _gethash($arr, &$rta, $path =array()) {
    if (is_array($arr)) {
      foreach ($arr as $key => $val) {
        $this->_gethash($arr[$key], $rta, array_merge(array($key), $path));
      }
    }
    else {
      $rta[$arr] = $path;
    }
  }

  // Function used from Google sample code to sign the cart contents with the merchant key.
  function CalcHmacSha1($data) {
    $key = $this->merchantkey;
    $blocksize = 64;
    $hashfunc = 'sha1';
    if (strlen($key) > $blocksize) {
      $key = pack('H*', $hashfunc($key));
    }
    $key = str_pad($key, $blocksize, chr(0x00));
    $ipad = str_repeat(chr(0x36), $blocksize);
    $opad = str_repeat(chr(0x5c), $blocksize);
    $hmac = pack(
                    'H*', $hashfunc(
                            ($key^$opad).pack(
                                    'H*', $hashfunc(
                                            ($key^$ipad).$data
                                    )
                            )
                    )
                );
    return $hmac; 
  }
		
  // Decides the shipping name to be used.
  // May not call this if the same name is to be used.
  // Useful if some one wants to map to Google checkout shopping types (flat, pickup or merchant calculate).
  function getShippingType($shipping_option) {
    switch($shipping_option) {
      case GOOGLECHECKOUT_FLAT_RATE_SHIPPING:
        return $this->ship_flat_ui .'- Flat Rate'; 
      case GOOGLECHECKOUT_ITEM_RATE_SHIPPING:
        return $this->ship_flat_ui .'- Item Rate';
      case GOOGLECHECKOUT_TABLE_RATE_SHIPPING:
        return $this->ship_flat_ui .'- Table Rate';
      default:
        return '';
    }	
  }
	
  // Function used to compute the actual price for shipping depending upon the shipping type selected.
  function getShippingPrice($ship_option, $cart, $actual_price, $handling = 0, $table_mode = '') {
	  // Flat, item, table
    switch($ship_option) {
      case 'flat':
        return $actual_price;	
      case 'item':
        return ($actual_price * $cart->count_contents()) + $handling;
      case 'table':
        // Check the mode to be used for pricing the shipping.
        if($table_mode == 'price') {
          $table_size = $cart->show_total();
        }
        elseif ($table_mode == 'weight') {
          $table_size = $cart->show_weight();
        }
					
        // Parse the price (value1:price1, value2:price2)						
        $tok = strtok($actual_price, ',');
        $tab_data = array();
        while($tok != FALSE) {
          $tab_data[] = $tok;
          $tok = strtok(',');
        }  
        $initial_val = 0;	  
        foreach($tab_data as $curr) {
          $final_val = strtok($curr, ':');
          $pricing = strtok(':'); 
          if($table_size >= $initial_val && $table_size <= $final_val) {
            $price = $pricing + $handling;
            break;
          }
          $initial_val = $final_val;
        }
        return $price;
      default:
        return 0;
    }		
  }

  // Class methods...
  function update_status() {
  }

  function javascript_validation() {
    return false;
  }

  function selection() {
    return array('id' => $this->code, 'module' => $this->title);
  }

  function pre_confirmation_check() {
    return false;
  }

  function confirmation() {
    return false;
  }

  function process_button() {
  }

  function before_process() {
    return false;
  }

  function after_process() {
    return false;
  }

  function output_error() {
    return false;
  }

  function check() {
    if (!isset($this->_check)) {
      $check_query = tep_db_query("select configuration_value from ". TABLE_CONFIGURATION ." where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS'");
      $this->_check = tep_db_num_rows($check_query);
    }
    return $this->_check;
  }

  function install() {
    global $language;
    require_once(DIR_FS_CATALOG .'includes/languages/'. $language .'/modules/payment/googlecheckout.php');
    $shipping_list = 'array(';
    foreach($this->shipping_display as $ship) {
      $shipping_list .= "'". $ship ."',";	 
    }
    $shipping_list = substr($shipping_list, 0, strlen($shipping_list) - 1);
    $shipping_list .= ")";
    tep_db_query("ALTER TABLE ". TABLE_CONFIGURATION ." CHANGE `configuration_value` `configuration_value` TEXT NOT NULL");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable GoogleCheckout Module', 'MODULE_PAYMENT_GOOGLECHECKOUT_STATUS', 'True', 'Accepts payments through Google Checkout on your site', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
	  tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('.htaccess Basic Authentication Mode with PHP over CGI?', 'MODULE_PAYMENT_GOOGLECHECKOUT_CGI', 'False', 'This configuration will <b>disable</b> PHP Basic Authentication in the responsehandler.php to validate Google Checkout messages.<br />If setted True you MUST configure your .htaccess files <a href=\"htaccess.php\" target=\"_OUT\">here</a>.', '6', '4', 'tep_cfg_select_option(array(\'False\', \'True\'),',now())");	
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant ID', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID', '', 'Your merchant ID is listed on the \"Integration\" page under the \"Settings\" tab', '6', '1', now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Merchant Key', 'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY', '', 'Your merchant key is also listed on the \"Integration\" page under the \"Settings\" tab', '6', '2', now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE', 'https://sandbox.google.com/checkout/', 'Select either the Developer\'s Sandbox or live Production environment', '6', '3', 'tep_cfg_select_option(array(\'https://sandbox.google.com/checkout/\', \'https://checkout.google.com/\'),',now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select Merchant Calculation Mode of Operation', 'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE', 'https', 'Merchant calculation URL for Sandbox environment. (Checkout production environment always requires HTTPS.)', '6', '4', 'tep_cfg_select_option(array(\'http\', \'https\'),',now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Disable Google Checkout for Virtual Goods?', 'MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS', 'False', 'If this configuration is enabled and there is any virtual good in the cart the Google Checkout button will be shown disabled.', '6', '4', 'tep_cfg_select_option(array(\'True\', \'False\'),',now())"); 

//	  tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('MultiSocket Shipping Quotes Retrieval', 'MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET', 'False', 'This configuration will enable a multisocket feature to parallelize Shipping Providers quotes. This should reduce the time this call take and avoid GC Merchant Calculation TimeOut. <a href=\"multisock.html\" target=\"_OUT\">More Info</a>.(Alfa)', '6', '4', 'tep_cfg_select_option(array(\'True\', \'False\'),',now())");	
	  tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Allow US PO BOX shipping?', 'MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX', 'True', 'Allow sending items to US PO Boxes?', '6', '4', 'tep_cfg_select_option(array(\'True\', \'False\'),',now())");	
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Default Values for Real Time Shipping Rates', 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING', '', 'Default values for real time rates in case the webservice call fails.', '6', '5',\"gc_cfg_select_shipping($shipping_list, \",now())");
	  tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rounding Policy Mode', 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE', 'HALF_EVEN', 'This configuration specifies the methodology that will be used to round values to two decimal places. <a href=\"http://code.google.com/apis/checkout/developer/Google_Checkout_Rounding_Policy.html\">More info</a>', '6', '4', 'tep_cfg_select_option(array(\'UP\',\'DOWN\',\'CEILING\',\'HALF_UP\',\'HALF_DOWN\', \'HALF_EVEN\'),',now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Rounding Policy Rule', 'MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE', 'PER_LINE', 'This configuration specifies when rounding rules should be applied to monetary values while Google Checkout is computing an order total.', '6', '4', 'tep_cfg_select_option(array(\'PER_LINE\',\'TOTAL\'),',now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Google Analytics Id', 'MODULE_PAYMENT_GOOGLECHECKOUT_ANALYTICS', 'NONE', 'Do you want to integrate the module with Google Analytics? Add your GA Id (UA-XXXXXX-X), NONE to disable. <br/> More info <a href=\'http://code.google.com/apis/checkout/developer/checkout_analytics_integration.html\'>here</a>', '6', '1', now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Continue shopping URL.', 'MODULE_PAYMENT_GOOGLECHECKOUT_CONTINUE_URL', 'index.php', 'Specify the page customers will be directed to if they choose to continue shopping after checkout.', '6', '8', now())");
    tep_db_query("insert into ". TABLE_CONFIGURATION ." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
    tep_db_query("create table if not exists ". $this->table_name ." (customers_id int(11), buyer_id bigint(20))");
    tep_db_query("create table if not exists ". $this->table_order ." (orders_id int(11), google_order_number bigint(20), order_amount decimal(15,4))");
  }

  function remove() {
    tep_db_query("delete from ". TABLE_CONFIGURATION ." where configuration_key in ('". implode("', '", $this->keys()) ."')");
    // If it is required to delete GC tables on removing the module, the two lines below could be uncommented.
    // tep_db_query("drop table " . $this->table_name);
    // tep_db_query("drop table " . $this->table_order);
  }

  function keys() {
    return array('MODULE_PAYMENT_GOOGLECHECKOUT_STATUS',
    					   'MODULE_PAYMENT_GOOGLECHECKOUT_CGI', 
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTID',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MERCHANTKEY',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MODE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_VIRTUAL_GOODS',
//					       'MODULE_PAYMENT_GOOGLECHECKOUT_MULTISOCKET',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_USPOBOX',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_TAXMODE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_TAXRULE',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_ANALYTICS',
                 'MODULE_PAYMENT_GOOGLECHECKOUT_CONTINUE_URL',
					       'MODULE_PAYMENT_GOOGLECHECKOUT_SORT_ORDER');
  }
}
// ** END GOOGLE CHECKOUT **
?>
