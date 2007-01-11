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

/* GOOGLE CHECKOUT
 * Script invoked when Google Checkout payment option has been enabled
 * It uses phpGCheckout library so it can work with PHP4 and PHP5
 * Generates the cart xml, shipping and tax options and adds them as hidden 
 * fields along with the Checkout button
 
 * A disabled button is displayed in the following cases:
 * 1. If merchant id or merchant key is not set 
 * 2. If there are multiple shipping options selected and they use different 
 * shipping tax tables or some dont use tax tables
 */

require_once('admin/includes/configure.php');
require_once('includes/languages/'. $language .'/modules/payment/googlecheckout.php');
require_once('includes/modules/payment/googlecheckout.php');

// Function which returns the current URL.
function gc_selfURL() {
	$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
	$protocol = gc_strleft(strtolower($_SERVER['SERVER_PROTOCOL']), '/') . $s;
	$port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'. $_SERVER['SERVER_PORT']);
	return $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
}

// Used by selfURL.
function gc_strleft($s1, $s2) {
	return substr($s1, 0, strpos($s1, $s2));
}

// Functions used to prevent SQL injection attacks.
function gc_makeSqlString($str) {
	return addcslashes(stripcslashes($str), "\"'\\\0..\37!@\@\177..\377");
}

function gc_makeSqlInteger($val) {
	return ((settype($val, 'integer')) ? ($val) : 0);
}

function gc_makeSqlFloat($val) {
	return ((settype($val, 'float')) ? ($val) : 0);
}

// Compare name:value and returns value for configurations parameters.
function gc_compare($key, $data)
{
	foreach($data as $value) {
		list($key2, $valor) = explode("_VD:", $value);
		if($key == $key2)		
			return $valor;
	}
	return '0';
}

$googlepayment = new googlecheckout();
$current_checkout_url = $googlepayment->checkout_url;

if (($googlepayment->merchantid == '') || ($googlepayment->merchantkey == '')) {
	$googlepayment->variant = 'disabled';
	$current_checkout_url = gc_selfURL();
}

// Create a cart and add items to it.
require('googlecheckout/gcxmlbuilder.php');

$gcheck = new gcXmlBuilder();
$gcheck->push('checkout-shopping-cart', array('xmlns' => 'http://checkout.google.com/schema/2'));
$gcheck->push('shopping-cart');
$gcheck->push('items');

$tax_array = array();
$tax_name_array = array();

$products = $cart->get_products();
if (sizeof($products) == 0) {
	$googlepayment->variant = 'disabled';
	$current_checkout_url = gc_selfURL();
}

for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
	if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
		while (list($option, $value) = each($products[$i]['attributes'])) {
			$attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, "
									              ."pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS
                                ." popt, ". TABLE_PRODUCTS_OPTIONS_VALUES ." poval, ". TABLE_PRODUCTS_ATTRIBUTES
                                ." pa where pa.products_id = '" . gc_makeSqlInteger($products[$i]['id']) . "' "
									              ."and pa.options_id = '" . gc_makeSqlString($option) . "' and pa.options_id = "
                                ."popt.products_options_id and pa.options_values_id = '" . gc_makeSqlString($value) . "' "
									              ."and pa.options_values_id = poval.products_options_values_id and "
                                ."popt.language_id = '" . $languages_id . "' and poval.language_id = '" 
                                . $languages_id . "'");
			$attributes_values = tep_db_fetch_array($attributes);
			$attr_value = $attributes_values['products_options_values_name'];
			$products[$i][$option]['products_options_name'] = $attributes_values['products_options_name'];
			$products[$i][$option]['options_values_id'] = $value;
			$products[$i][$option]['products_options_values_name'] = $attr_value;
			$products[$i][$option]['options_values_price'] = $attributes_values['options_values_price'];
			$products[$i][$option]['price_prefix'] = $attributes_values['price_prefix'];
		}
	}
	$products_name = $products[$i]['name'];
	$products_description = tep_db_fetch_array(tep_db_query("select products_description from "
	                          . TABLE_PRODUCTS_DESCRIPTION . " where products_id = '" . $products[$i]['id']
                             ."' and language_id = '" . $languages_id . "'"));
	$products_description = $products_description['products_description'];
	$tax_result = tep_db_query("select tax_class_title from ". TABLE_TAX_CLASS 
                            ." where tax_class_id = ". gc_makeSqlInteger($products[$i]['tax_class_id']));
	$tax = tep_db_fetch_array($tax_result);
	$tt = $tax['tax_class_title'];
	if (!in_array($products[$i]['tax_class_id'], $tax_array)) {
		$tax_array[] = $products[$i]['tax_class_id'];
		$tax_name_array[] = $tt;
	}
	if (isset ($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
		reset($products[$i]['attributes']);
		while (list($option, $value) = each($products[$i]['attributes'])) {
			$products_name .= "\n- ". $products[$i][$option]['products_options_name'] .' '
			                . $products[$i][$option]['products_options_values_name'];
		}
	}
	$gcheck->push('item');
	$gcheck->element('item-name', $products_name);
	$gcheck->element('item-description', $products_description);
	$gcheck->element('unit-price', $products[$i]['final_price'], array ('currency' => 'USD'));
	$gcheck->element('quantity', $products[$i]['quantity']);
	$gcheck->element('tax-table-selector', $tt);
	$gcheck->pop('item');
}
$gcheck->pop('items');

$private_data = tep_session_id() .';'. tep_session_name();
$product_list = '';
for ($i = 0, $n = sizeof($products); $i < $n; $i++) {
	$product_list .= ";". (int)$products[$i]['id'];
}
$gcheck->push('merchant-private-data');
$gcheck->element('session-data', $private_data);
$gcheck->element('product-data', $product_list);
$gcheck->pop('merchant-private-data');

$gcheck->pop('shopping-cart');

$gcheck->push('checkout-flow-support');
$gcheck->push('merchant-checkout-flow-support');

$gcheck->element('edit-cart-url', tep_href_link('shopping_cart.php'));
$gcheck->element('continue-shopping-url', tep_href_link($googlepayment->continue_url));

//Shipping options
$gcheck->push('shipping-methods');

$tax_class = array ();
$shipping_arr = array ();
$tax_class_unique = array ();

$shipping_array = tep_db_fetch_array(tep_db_query("select configuration_value from " . TABLE_CONFIGURATION 
                                    ." where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING'"));
$options = explode(", ", $shipping_array['configuration_value']);

// Get the properties of the shipping methods.
$module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';

$file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
$directory_array = array();
if ($dir = @ dir($module_directory)) {
	while ($file = $dir->read()) {
		if (!is_dir($module_directory . $file)) {
			if (substr($file, strrpos($file, '.')) == $file_extension) {
				$directory_array[] = $file;
			}
		}
	}
	sort($directory_array);
	$dir->close();
}

$module_info = array();
for ($i = 0, $n = sizeof($directory_array); $i < $n; $i++) {
	$file = $directory_array[$i];

	include_once (DIR_FS_CATALOG_LANGUAGES . $language . '/modules/shipping/' . $file);
	include_once ($module_directory . $file);

	$class = substr($file, 0, strrpos($file, '.'));
	$module = new $class;
	if ($module->enabled == true) {
		$module_info[$module->code] = array(
      'code' => $module->code,
			'title' => $module->title,
			'description' => $module->description,
			'status' => $module->check());
	}
}

// Check if there is a shipping module activated that is not flat rate.
// To enable Merchant Calculations, if there are flat and MC both will be MC.
$ship_calculation_mode = (count(array_keys($module_info)) > count(array_intersect($googlepayment->shipping_support, array_keys($module_info)))) ? true : false;
                             
$calculations_array = tep_db_fetch_array(tep_db_query("select configuration_value from " . TABLE_CONFIGURATION
                                        ." where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING'"));
$key_values = explode(", ", $calculations_array['configuration_value']);

foreach ($module_info as $key => $value) {
	// Check if the shipping method is activated.
	$module_name = $module_info[$key]['code'];
	$curr_ship = strtoupper($module_name);
	$check_query = tep_db_query("select configuration_key,configuration_value from " . TABLE_CONFIGURATION
                             ." where configuration_key LIKE 'MODULE_SHIPPING_" . $curr_ship . "_%' ");
	$num_rows = tep_db_num_rows($check_query);
	$data_arr = array();
	$handling = 0;
	$table_mode = '';

	for ($j = 0; $j < $num_rows; $j++) {
		$flat_array = tep_db_fetch_array($check_query);
		$data_arr[$flat_array['configuration_key']] = $flat_array['configuration_value'];
	}
	$common_string = 'MODULE_SHIPPING_'. $curr_ship .'_';
	$zone = $data_arr[$common_string .'ZONE'];
	$enable = $data_arr[$common_string .'STATUS'];
	$curr_tax_class = $data_arr[$common_string .'TAX_CLASS'];
	$price = $data_arr[$common_string .'COST'];
	$handling = $data_arr[$common_string .'HANDLING'];
	$table_mode = $data_arr[$common_string .'MODE'];
	if ($zone != '') {
		$zone_result = tep_db_query("select countries_name, zone_code from " . TABLE_GEO_ZONES
                               ." as gz, ". TABLE_ZONES_TO_GEO_ZONES ." as ztgz, ". TABLE_ZONES
                               ." as z, ". TABLE_COUNTRIES ." as c where gz.geo_zone_id = ". $zone
                               ." and gz.geo_zone_id = ztgz.geo_zone_id and ztgz.zone_id = z.zone_id"
                               ." and z.zone_country_id = c.countries_id ");
		$allowed_restriction_state = $allowed_restriction_country = array();
		// Get all the allowed shipping zones.
		while($zone_answer = tep_db_fetch_array($zone_result)) {
			$allowed_restriction_state[] = $zone_answer['zone_code'];
			$allowed_restriction_country[] = array($zone_answer['countries_name'], $zone_answer['countries_iso_code_2']);
		}

	}
	
	if ($curr_tax_class != 0 && $curr_tax_class != '') {
		$tax_class[] = $curr_tax_class;
		if (!in_array($curr_tax_class, $tax_class_unique))
			$tax_class_unique[] = $curr_tax_class;
	}

	if ($enable == "True") {
		foreach($googlepayment->mc_shipping_methods[$key] as $shipping_type){
			foreach($shipping_type as $method => $name){
			
//	['domestic_types']
			// merchant calculated shipping
				if ($ship_calculation_mode == 'True') {
						$gcheck->push('merchant-calculated-shipping', array ('name' => $googlepayment->mc_shipping_methods_names[$module_info[$key]['code']] . ': ' . $name));
				} 
			// flat rate shipping 
				else {
					$gcheck->push('flat-rate-shipping', array ('name' => $module_info[$key]['title']));
				}	
				if(!in_array($module_info[$key]['code'], $googlepayment->shipping_support)) {
			
					$gcheck->element('price', gc_compare($module_info[$key]['code'].$method ,$key_values), array ('currency' => 'USD'));
					// 8245366
				}
				// flat rate shipping
				else {
					$price = $googlepayment->getShippingPrice($module_name, $cart, $price, $handling, $table_mode);
					$gcheck->element('price', $price, array ('currency' => 'USD'));
				}
					
				$gcheck->push('shipping-restrictions');
				$gcheck->push('allowed-areas');
				if(!empty($allowed_restriction_country)){
					foreach($allowed_restriction_state as $state_key => $state) {
						if($allowed_restriction_country[$state_key][1] == 'US') {
							$gcheck->push('us-state-area');
							$gcheck->element('state', $state);
							$gcheck->pop('us-state-area');
						}
					}
				}
				else {
					$gcheck->element('us-country-area', '', array ('country-area' => 'ALL'));
				}
				$gcheck->pop('allowed-areas');
				$gcheck->pop('shipping-restrictions');
			
				if ($ship_calculation_mode == 'True') {
					$gcheck->pop('merchant-calculated-shipping');
				} 
				else {
					$gcheck->pop('flat-rate-shipping');
				}
			}
		}
	}
}

$gcheck->pop('shipping-methods');
$gcheck->element('request-buyer-phone-number', 'true');

if($ship_calculation_mode == 'True') {
	$calculations_array = tep_db_fetch_array(tep_db_query("select configuration_value from " . TABLE_CONFIGURATION
                                          ." where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_MODE'"));
	$srv_mode = $calculations_array['configuration_value'];

	$calculations_array = tep_db_fetch_array(tep_db_query("select configuration_value from " . TABLE_CONFIGURATION
                                          ." where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_MC_MODE' "));
	$http_mode = $calculations_array['configuration_value'];

  if ($srv_mode == 'https://sandbox.google.com/checkout/' && $http_mode == 'http') {
    $url = HTTP_SERVER . DIR_WS_CATALOG .'googlecheckout/responsehandler.php';
  }
  else {
    $url = HTTPS_SERVER . DIR_WS_CATALOG .'googlecheckout/responsehandler.php';
	}

	$gcheck->push('merchant-calculations');
	$gcheck->element('merchant-calculations-url', $url);
	$gcheck->pop('merchant-calculations');
}

//Tax options	
$gcheck->push('tax-tables');
$gcheck->push('default-tax-table');
$gcheck->push('tax-rules');

if (sizeof($tax_class_unique) == 1 && sizeof($options) == sizeof($tax_class)) {
  $tax_rates_result = tep_db_query("select countries_name, zone_code, tax_rate from ". TABLE_TAX_RATES
                                  ." as tr, ". TABLE_ZONES_TO_GEO_ZONES ." as ztgz, ". TABLE_ZONES
                                  ." as z, ". TABLE_COUNTRIES ." as c where tr.tax_class_id = "
                                  . $tax_class_unique[0] ." and tr.tax_zone_id = ztgz.geo_zone_id "
                                  ."and ztgz.zone_id = z.zone_id and ztgz.zone_country_id = c.countries_id");
	$num_rows = tep_db_num_rows($tax_rates_result);
	$tax_rule = array();

	for ($j = 0; $j < $num_rows; $j++) {
		$tax_result = tep_db_fetch_array($tax_rates_result);
		$rate = ((double) ($tax_result['tax_rate'])) / 100.0;

		$gcheck->push('default-tax-rule');
		$gcheck->element('shipping-taxed', 'true');
		$gcheck->element('rate', $rate);
		$gcheck->push('tax-area');
		$gcheck->push('us-state-area');
		$gcheck->element('state', $tax_result['zone_code']);
		$gcheck->pop('us-state-area');
		$gcheck->pop('tax-area');
		$gcheck->pop('default-tax-rule');
	}
}
else {
	$gcheck->push('default-tax-rule');
	$gcheck->element('rate', 0);
	$gcheck->push('tax-area');
	$gcheck->element('us-country-area', '', array ('country-area' => 'ALL'));
	$gcheck->pop('tax-area');
	$gcheck->pop('default-tax-rule');
}
$gcheck->pop('tax-rules');
$gcheck->pop('default-tax-table');

if (sizeof($tax_class_unique) > 1 || (sizeof($tax_class_unique) == 1 && sizeof($options) != sizeof($tax_class))) {
	$googlepayment->variant = "disabled";
	$current_checkout_url = gc_selfURL();
}

$i = 0;
$tax_tables = array ();
$gcheck->push('alternate-tax-tables');

foreach ($tax_array as $tax_table) {
	$tax_rates_result = tep_db_query("select countries_name, zone_code, tax_rate 
			                                       from " . TABLE_TAX_RATES . " as tr, 
			                                      " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz, 
			                                      " . TABLE_ZONES . " as z, 
			                                      " . TABLE_COUNTRIES . " as c 
			                                      where tr.tax_class_id= " . $tax_array[$i] . " 
			                                      and tr.tax_zone_id = ztgz.geo_zone_id 
			                                      and ztgz.zone_id=z.zone_id 
			                                      and ztgz.zone_country_id = c.countries_id");
	$num_rows = tep_db_num_rows($tax_rates_result);
	$tax_rule = array ();

	$gcheck->push('alternate-tax-table', array (
		'name' => (!empty($tax_name_array[$i])?$tax_name_array[$i]:'none')
	));
	$gcheck->push('alternate-tax-rules');
	for ($j = 0; $j < $num_rows; $j++) {
		$tax_result = tep_db_fetch_array($tax_rates_result);
		$rate = ((double) ($tax_result['tax_rate'])) / 100.0;
		$gcheck->push('alternate-tax-rule');
		$gcheck->element('rate', $rate);
		$gcheck->push('tax-area');
		$gcheck->push('us-state-area');
		$gcheck->element('state', $tax_result['zone_code']);
		$gcheck->pop('us-state-area');
		$gcheck->pop('tax-area');
		$gcheck->pop('alternate-tax-rule');
	}
	$gcheck->pop('alternate-tax-rules');
	$gcheck->pop('alternate-tax-table');
	$i++;
}
$gcheck->pop('alternate-tax-tables');
$gcheck->pop('tax-tables');

$gcheck->pop('merchant-checkout-flow-support');
$gcheck->pop('checkout-flow-support');
$gcheck->pop('checkout-shopping-cart');

//if ($debug = fopen('googlecheckout/sent_message.log', 'w')) {
//  fwrite($debug, 'Cart compiled '. date("D M j G:i:s T Y") ."\n\n");
//  fwrite($debug, $gcheck->getXml() ."\n\n");
//}

?>
<table border="0" width="98%" cellspacing="1" cellpadding ="1"> 
<tr><br>
<td align="right" valign="middle" class = "main">
 <B><?php echo MODULE_PAYMENT_GOOGLECHECKOUT_TEXT_OPTION ?> </B>
</td></tr>
</table> 
  
<table  border="0" width="100%" class="table-1" cellspacing="12" cellpadding="5"> 
  <!-- Print Error message if the shopping cart XML is invalid -->

  <!-- Print the Google Checkout button in a form containing the shopping cart data -->
  <tr><td align="right" valign="middle" class = "main">
    <p><form method="POST" action="<?php echo $current_checkout_url; ?>">
     <input type="hidden" name="cart" value="<?php echo base64_encode($gcheck->getXml());?>">
     <input type="hidden" name="signature" value="<?php echo base64_encode( $googlepayment->CalcHmacSha1($gcheck->getXml())); ?>">
	   <input type="image" name="Checkout" alt="Checkout" 
            src="<?php echo $googlepayment->mode;?>buttons/checkout.gif?merchant_id= <?php echo $googlepayment->merchantid;?>&w=180&h=46&style=white&variant=<?php echo $googlepayment->variant;?>&loc=en_US"height="46" width="180">  
        </form></p>
    </td></tr>
</table>
<!-- ** END GOOGLE CHECKOUT ** -->