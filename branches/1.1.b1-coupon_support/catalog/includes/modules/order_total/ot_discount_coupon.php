<?php
/*
 * ot_discount_coupons.php 
 * August 4, 2006 
 * author: Kristen G. Thorson
 * 
 * ot_discount_coupon_codes version 2.0
 * 
 * Released under the GNU General Public License
 *
 */

 	//try removing this line if you're getting an error like Fatal error: Cannot redeclare class discount_coupon in ***path to shop***/includes/classes/discount_coupon.php on line 14
 	require_once( DIR_FS_CATALOG.DIR_WS_CLASSES.'discount_coupon.php' );

  class ot_discount_coupon {
    var $title, $output, $coupon;

    function ot_discount_coupon() {
      $this->code = 'ot_discount_coupon';
      $this->title = MODULE_ORDER_TOTAL_DISCOUNT_COUPON_TITLE;
      $this->enabled = ((MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS == 'true') ? true : false);
      $this->sort_order = MODULE_ORDER_TOTAL_DISCOUNT_COUPON_SORT_ORDER;

      $this->output = array();
    }

    function process() {
      global $order, $currencies;
      if( tep_not_null( $order->info['coupon'] ) ) {
      	$this->coupon = new discount_coupon( $order->info['coupon'] );

	      //print_r( $order ); //kgt - use this to debug order object contents
	      //print_r( $this->coupon ); //kgt - use this to debug coupon object contents

	      //if the order total lines for multiple tax groups should be displayed as one, add them all together
	      if( MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_LINES == 'false' ) $discount = array( array_sum( $order->info['applied_discount'] ) );
				else $discount = $order->info['applied_discount'];
		    foreach( $discount as $key => $value ) {
		      if ($value > 0) {
		        $display_type = ( MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_TYPE == 'true' ? '-' : '' );
		        $this->output[] = array('title' => $this->format_display( $order->info['coupon'], $key ) . ':',
		                                'text' => $display_type.$currencies->format( $value, true, $order->info['currency'], $order->info['currency_value'] ),
		                                'value' => $display_type.$value);
		      }
		    }
	  	} else $this->enabled = false;
    }

    function format_display( $coupon, $tax_group ) {
    	global $order, $currencies;
    	//if using multiple languages, get the language format string from the proper language file, otherwise, use the module configuration field
    	$display = ( MODULE_ORDER_TOTAL_DISCOUNT_COUPON_USE_LANGUAGE_FILE == 'true' ? MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY : MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_FORMAT );
    	//replace the variables with their proper values:
			$display = str_replace( '[code]', $this->coupon->code, $display );
			$display = str_replace( '[percent_discount]', ( $this->coupon->coupon['coupons_discount_percent'] * 100 ).'%', $display );
			$display = str_replace( '[coupon_desc]', $this->coupon->coupon['coupons_description'], $display );
			$display = str_replace( '[coupons_min_order]', $currencies->format( $this->coupon->coupon['coupons_min_order'], true, $order->info['currency'], $order->info['currency_value'] ), $display );
			$display = str_replace( '[coupons_number_available]', $this->coupon->coupon['coupons_number_available'], $display );
			$display = str_replace( '[tax_desc]', $tax_group, $display );
			return $display;
		}

    function check() {
      if (!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS'");
        $this->_check = tep_db_num_rows($check_query);
      }

      return $this->_check;
    }

    function keys() {
      return array('MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_SORT_ORDER', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_TYPE', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_SUBTOTAL', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_RANDOM_CODE_LENGTH', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_LINES', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_USE_LANGUAGE_FILE', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_FORMAT');
    }

    function install() {
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Discount Coupon', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS', 'true', 'Do you want to display the discount coupon value?', '615', '1','tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_SORT_ORDER', '0', 'Order in which the discount coupon code order total line will be displayed on order confirmation, invoice, etc.', '615', '2', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Discount with Minus (-) Sign', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_TYPE', 'true', '<b>true</b> - the discount will be displayed with a minus sign<br><b>false</b> - the discount will be displayed without a minus sign', '615', '3', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Subtotal with Applied Discount', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_SUBTOTAL', 'true', '<b>true</b> - the order subtotal will be displayed with the discount applied<br><b>false</b> - the order subtotal will be displayed without the discount applied', '615', '4', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Random Code Length', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_RANDOM_CODE_LENGTH', '6', 'Length for randomly generated coupon codes.', '615', '5', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Display Discount Total Lines for Each Tax Group?', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_LINES', 'false', '<b>true</b> - the discount coupon order total lines will be displayed for each tax group for the order<br><b>false</b> - the discount order total lines will be combined and displayed as one line', '615', '6', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Use the language file to format display string?', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_USE_LANGUAGE_FILE', 'false', '<b>true</b> - use the format found in language file (used for when you have multiple languages and want the order total line to format display depending on language choice)<br><b>false</b> - use the format and language below', '615', '7', 'tep_cfg_select_option(array(\'true\', \'false\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Display Format for Order Total Line', 'MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_FORMAT', 'Discount Coupon [code] applied', 'Display format for the discount coupon code order total line.<br><br>Variables:<br>[code]<br>[coupon_desc]<br>[percent_discount]<br>[coupons_min_order]<br>[coupons_number_available]<br>[tax_desc]', '615', '8', now())");
    }

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }
  }
?>