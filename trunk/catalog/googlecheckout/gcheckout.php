<?php

/* GOOGLE CHECKOUT
 * Script invoked when Google Checkout payment option has been enabled
 * It uses phpGCheckout library so it can work with PHP4 and PHP5
 * Generates the cart xml, shipping and tax options and adds them as hidden fields
 * along with the Checkout button
 
 * A disabled button is displayed in the following cases:
 * 1. If merchant id or merchant key is not set 
 * 2. If there are multiple shipping options selected and they use different shipping tax tables
 *  or some dont use tax tables
 */
  
  require_once('admin/includes/configure.php');
  require_once('includes/languages/' . $language . '/' .'modules/payment/googlecheckout.php');
  require_once('includes/modules/payment/googlecheckout.php');
  
  function selfURL() { 
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
	$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s; 
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
  } 
  function strleft($s1, $s2) { 
    return substr($s1, 0, strpos($s1, $s2)); 
  }

  $googlepayment = new googlecheckout();
  $current_checkout_url = $googlepayment->checkout_url;  
  tep_session_register('current_checkout_url');
  
  if($googlepayment->merchantid == '' || $googlepayment->merchantkey == '')
    $googlepayment->variant = "disabled";

  //Create a cart and add items to it  
	require('googlecheckout/xmlwriter.php');
	$gcheck = new XmLWriter();

	$gcheck->push('checkout-shopping-cart', array('xmlns' => "http://checkout.google.com/schema/2"));
	$gcheck->push('shopping-cart');
	$gcheck->push('items');
	
	$products = $cart->get_products();
  $tax_array = array();
  $tax_name_aray = array();
		
  for ($i=0, $n=sizeof($products); $i<$n; $i++) {
    if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes']))  {
      while (list($option, $value) = each($products[$i]['attributes'])) {
        $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix
                                      from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                      where pa.products_id = '" . $products[$i]['id'] . "'
                                        and pa.options_id = '" . $option . "'
                                        and pa.options_id = popt.products_options_id
                                        and pa.options_values_id = '" . $value . "'
                                        and pa.options_values_id = poval.products_options_values_id
                                        and popt.language_id = '" . $languages_id . "'
                                        and poval.language_id = '" . $languages_id . "'");
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
    $products_description = tep_db_fetch_array(tep_db_query("select products_description from ".TABLE_PRODUCTS_DESCRIPTION. " where products_id = '" . $products[$i]['id'] . "' and language_id = '". $languages_id ."'"));
    $products_description = $products_description['products_description'];
      
	  $tax_result = tep_db_query("select tax_class_title from " . TABLE_TAX_CLASS . " where tax_class_id = " . $products[$i]['tax_class_id'] );
    $tax = tep_db_fetch_array($tax_result);
	  $tt = $tax['tax_class_title'];
	  if(!in_array($products[$i]['tax_class_id'], $tax_array)) {
      $tax_array[] = $products[$i]['tax_class_id'];	  
      $tax_name_array[] = $tt;
    }
	  if (isset($products[$i]['attributes']) && is_array($products[$i]['attributes'])) {
      reset($products[$i]['attributes']);
      while (list($option, $value) = each($products[$i]['attributes'])) {
        $products_name .= "\n" .'- ' . $products[$i][$option]['products_options_name'] . ' ' . $products[$i][$option]['products_options_values_name'] . '';
      }
    }
		$gcheck->push('item');
		$gcheck->element('item-name', $products_name);
		$gcheck->element('item-description', $products_description);
    $gcheck->element('unit-price', $products[$i]['final_price'], array('currency'=> 'USD'));
		$gcheck->element('quantity', $products[$i]['quantity']);
  	$gcheck->element('tax-table-selector', $tt);
		$gcheck->pop('item');
  }
	$gcheck->pop('items'); 
	
	$private_data = tep_session_id().';'.tep_session_name();
  	$product_list = '';
	for ($i=0, $n=sizeof($products); $i<$n; $i++) {
	  $product_list .= ";".(int)$products[$i]['id'];
	}
  $gcheck->push('merchant-private-data');
	$gcheck->element('session-data', $private_data);
	$gcheck->element('product-data', $product_list);
	$gcheck->pop('merchant-private-data');
	
	$gcheck->pop('shopping-cart');
	
	//Add the starting index file as the return url for the buyer.
	// This can be added as an option during the module installation
  $cont_shopping_cart = tep_href_link(FILENAME_DEFAULT);
  $gcheck->push('checkout-flow-support');
	$gcheck->push('merchant-checkout-flow-support');
	$gcheck->element('continue-shopping-url', $cont_shopping_cart);
			
	  //Shipping options
	$gcheck->push('shipping-methods');
  $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_GOOGLECHECKOUT_SHIPPING' ");
  $shipping_array = tep_db_fetch_array($check_query);
  $ship =$shipping_array['configuration_value'];
  $tax_class = array();
  $shipping_arr = array();
  $tax_class_unique = array();
	
  $options = array();
  $tok = strtok($ship," ");
  while($tok != FALSE) {
    $options[] = $tok;
	$tok = strtok(" ");
  }
  
  for($i=0; $i< sizeof($googlepayment->shipping_support); $i++) {
    if(in_array($googlepayment->shipping_support[$i], $options))  {   	  
	    $curr_ship = strtoupper($googlepayment->shipping_support[$i]);
      $check_query = tep_db_query("select configuration_key,configuration_value from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE_SHIPPING_" . $curr_ship . "_%' ");
	    $num_rows = tep_db_num_rows($check_query);
  	  $name = $googlepayment->getShippingType($googlepayment->shipping_support[$i]);
	    $data_arr = array();
	    $handling = 0;
	    $table_mode = '';
	  
	    for($j=0; $j < $num_rows; $j++)  {
   	    $flat_array = tep_db_fetch_array($check_query);
		    $data_arr[$flat_array['configuration_key']]= $flat_array['configuration_value'];
	    }
      $common_string = "MODULE_SHIPPING_".$curr_ship."_";
	    $zone = $data_arr[$common_string."ZONE"]; 	
	    $enable = $data_arr[$common_string."STATUS"];
	    $curr_tax_class = $data_arr[$common_string."TAX_CLASS"];
	    $price = $data_arr[$common_string."COST"];
	    $handling = $data_arr[$common_string."HANDLING"];
	    $table_mode = $data_arr[$common_string."MODE"];
	    $price = $googlepayment->getShippingPrice($googlepayment->shipping_support[$i], $cart, $price, $handling, $table_mode);
     	  	  		  
	    $zone_result = tep_db_query("select countries_name, zone_code from " . TABLE_GEO_ZONES . " as gz ," . TABLE_ZONES_TO_GEO_ZONES . " as ztgz," . TABLE_ZONES . " as z, ". TABLE_COUNTRIES . " as c where gz.geo_zone_id = " . $zone. " and gz.geo_zone_id = ztgz.geo_zone_id and ztgz.zone_id = z.zone_id and z.zone_country_id = c.countries_id ");
	    $zone_answer = tep_db_fetch_array($zone_result);
	    $allowed_restriction_state = $zone_answer['zone_code'];
	    $allowed_restriction_country = $zone_answer['countries_name'];

	    if($enable == "True") {
		    if($curr_tax_class != 0 && $curr_tax_class != '') {
		      $tax_class[] = $curr_tax_class;
		      if(!in_array($curr_tax_class, $tax_class_unique))
	          $tax_class_unique[] = $curr_tax_class;  	
		    } 
				$gcheck->push('flat-rate-shipping', array('name' => $name));
				$gcheck->element('price', $price, array('currency' => 'USD'));
				$gcheck->push('allowed-areas');
				if($allowed_restriction_country == '')
				  $gcheck->element('us-country-area','', array('country-area' => 'ALL'));
				else { 
					$gcheck->push('us-state-area');
				  $gcheck->element('state', $allowed_restriction_state);
					$gcheck->pop('us-state-area');
				}
				$gcheck->pop('allowed-areas');
   			$gcheck->pop('flat-rate-shipping');
	    }
    }
  } 
  $gcheck->pop('shipping-methods');

  //Tax options	
  $gcheck->push('tax-tables');
	$gcheck->push('default-tax-table');
	$gcheck->push('tax-rules');

		
  if(sizeof($tax_class_unique) == 1  && sizeof($shipping_arr) == sizeof($tax_class)) {
    $tax_rates_result =  tep_db_query("select countries_name, zone_code, tax_rate from " . TABLE_TAX_RATES . " as tr, " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz, " . TABLE_ZONES . " as z, " . TABLE_COUNTRIES . " as c where tr.tax_class_id= " . $tax_class_unique[0] . " and tr.tax_zone_id = ztgz.geo_zone_id and ztgz.zone_id=z.zone_id and ztgz.zone_country_id = c.countries_id");
	  $num_rows = tep_db_num_rows($tax_rates_result);
    $tax_rule = array();
	  
	  for($j=0; $j<$num_rows; $j++) {
	    $tax_result = tep_db_fetch_array($tax_rates_result);
	    $rate = ((double) ($tax_result['tax_rate']))/100.0;
			
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
    $tax_table = new gTaxTable($tax_name_array[0], $tax_rule, TAX_TABLE_DEFAULT); 
	} else {
		$gcheck->push('default-tax-rule');			
		$gcheck->element('rate', 0);
    $gcheck->push('tax-area');			
		$gcheck->element('us-country-area','', array('country-area'=>'ALL'));
    $gcheck->pop('tax-area');			
    $gcheck->pop('default-tax-rule');			
	}
	$gcheck->pop('tax-rules');
	$gcheck->pop('default-tax-table');
 
  if(sizeof($tax_class_unique) > 1 || (sizeof($tax_class_unique) == 1 && sizeof($shipping_arr) != sizeof($tax_class) ))  {
    $googlepayment->variant = "disabled";	
	  $current_checkout_url = selfURL();
  }
	
  $i=0;
  $tax_tables = array();
	$gcheck->push('alternate-tax-tables');
	
  foreach($tax_array as $tax_table)  {
    $tax_rates_result =  tep_db_query("select countries_name, zone_code, tax_rate from " . TABLE_TAX_RATES . " as tr, " . TABLE_ZONES_TO_GEO_ZONES . " as ztgz, " . TABLE_ZONES . " as z, " . TABLE_COUNTRIES . " as c where tr.tax_class_id= " . $tax_array[$i]. " and tr.tax_zone_id = ztgz.geo_zone_id and ztgz.zone_id=z.zone_id and ztgz.zone_country_id = c.countries_id");	
	  $num_rows = tep_db_num_rows($tax_rates_result);
	  $tax_rule = array();

		$gcheck->push('alternate-tax-table',array('name' => $tax_name_array[$i]));
		$gcheck->push('alternate-tax-rules');
	  for($j=0; $j<$num_rows; $j++) {
	    $tax_result = tep_db_fetch_array($tax_rates_result);
	    $rate = ((double)($tax_result['tax_rate']))/100.0;
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
	
?>

<html>
<head>
    <style type="text/css">@import url(googleCheckout.css);</style>
</head>
<body>
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
     <input type="hidden" name="signature" value="<?php echo base64_encode($googlepayment->CalcHmacSha1($gcheck->getXml()));?>"> 
	   <input type="image" name="Checkout" alt="Checkout" 
            src="<?php echo $googlepayment->mode;?>buttons/checkout.gif?merchant_id=<? echo $googlepayment->merchantid;?>&w=180&h=46&style=white&variant=<? echo $googlepayment->variant;?>&loc=en_US" 
            height="46" width="180">
        </form></p>
    </td></tr>
</table>
</body>
</html> 

<!-- ** END GOOGLE CHECKOUT ** -->