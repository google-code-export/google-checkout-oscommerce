<?php
/*
 * ot_discount_coupon.php
 * August 4, 2006
 * author: Kristen G. Thorson
 *
 * ot_discount_coupon_codes version 2.0
 *
 * Released under the GNU General Public License
 *
 */


  class discount_coupon {

  	var $code, $error_message, $coupon;

  	function discount_coupon( $code ) {
  		$this->code = $code;
			$this->error_message = array();
			$this->coupon = array();
			$this->get_coupon();
		}

		function verify_code() {
	    //check the global number of discounts that may be used
			if( $this->coupon['coupons_number_available'] != 0 ) {
				$this->check_num_available();
			}
			//if coupons_max_use==0, then use is unlimited, otherwise, we need to verify the customer hasn't used this coupon more than coupons_max_use times
			if( $this->coupon['coupons_max_use'] != 0 ) {
				$this->check_coupons_max_use();
			}
			//now we need to check if the order total matches the coupons_min_order
			if( $this->coupon['coupons_min_order'] != 0 ) {
				$this->check_coupons_min_order();
			}
		}

		function get_coupon() {
      $check_code_query = tep_db_query( $sql = "SELECT coupons_discount_percent, coupons_description, coupons_max_use, coupons_min_order, coupons_max_order, coupons_number_available
	  																						FROM " . TABLE_DISCOUNT_COUPONS . "
	  																						WHERE coupons_id = '" . tep_db_input( $this->code ) . "'
	  																							AND ( coupons_date_start <= CURDATE() OR coupons_date_start IS NULL )
	  																							AND ( coupons_date_end >= CURDATE() OR coupons_date_end IS NULL )" );
			if( tep_db_num_rows( $check_code_query ) != 1 ) { //if no rows are returned, then they haven't entered a valid code
				$this->error_message[] = ENTRY_DISCOUNT_COUPON_ERROR; //display the error message
			} else {
				$row = tep_db_fetch_array( $check_code_query ); //since there is one record, we have a valid code
        $this->coupon = $row;
			}
		}

		function check_coupons_min_order() {
			global $order, $currencies;
      //if we display the subtotal without the discount applied, then just compare the subtotal to the minimum order
			if( MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_SUBTOTAL == 'false' && $this->coupon['coupons_min_order'] > $order->info['subtotal'] ) {
				$this->error_message[] = sprintf( ENTRY_DISCOUNT_COUPON_MIN_ERROR, $currencies->format( $this->coupon['coupons_min_order'], true, $order->info['currency'], $order->info['currency_value'] ) );
			//if we display the subtotal with the discount applied, then we need to compare the subtotal with the discount added back in to the minimum order
			} else if( MODULE_ORDER_TOTAL_DISCOUNT_COUPON_DISPLAY_SUBTOTAL == 'true' ) {
			  $subtotal = $order->info['subtotal'];
      	foreach( $order->info['applied_discount'] as $discount ) {
					$subtotal += $discount;
				}
				if( $this->coupon['coupons_min_order'] > $subtotal ) $this->error_message[] = sprintf( ENTRY_DISCOUNT_COUPON_MIN_ERROR, $currencies->format( $this->coupon['coupons_min_order'], true, $order->info['currency'], $order->info['currency_value'] ) );
			}
		}

		function check_coupons_max_use() {
			global $customer_id;
      $check_use_query = tep_db_query($sql = "SELECT COUNT(*) AS cnt
      																				FROM ".TABLE_ORDERS." AS o
      																				INNER JOIN ".TABLE_DISCOUNT_COUPONS_TO_ORDERS." dc2o
      																					ON dc2o.orders_id=o.orders_id
      																					AND o.customers_id = '".(int)$customer_id."'
      																					AND dc2o.coupons_id='".tep_db_input( $this->code )."'");
			$use = tep_db_fetch_array( $check_use_query );
			//show error message if coupons_max_use is equal to the number of times this customer has used the code
			if( $this->coupon['coupons_max_use'] <= $use['cnt'] ) $this->error_message[] = sprintf( ENTRY_DISCOUNT_COUPON_USE_ERROR, $use['cnt'], $this->coupon['coupons_max_use'] ); //display the error message for number of times used
		}

		function check_num_available() {
      //count the number of times this coupon has been used
			$check_use_query = tep_db_query( $sql = 'SELECT COUNT(*) AS cnt
																							 FROM '.TABLE_DISCOUNT_COUPONS_TO_ORDERS.'
																							 WHERE coupons_id="'.tep_db_input( $this->code ).'"' );
			$use = tep_db_fetch_array( $check_use_query );
			if( $this->coupon['coupons_number_available'] <= $use['cnt'] ) $this->error_message[] = ENTRY_DISCOUNT_COUPON_AVAILABLE_ERROR; //display error that this coupon is no longer valid
		}

		function is_recalc_shipping() {
			global $order, $language;
      //check if there is free shipping
			if( MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true' ) {
				include(DIR_WS_LANGUAGES . $language . '/modules/order_total/ot_shipping.php');
				//if free shipping is enabled, make sure te discount does not bring the order total below free shipping limit
				if( $order->info['shipping_method'] == FREE_SHIPPING_TITLE ) { //if free shipping is the selected shipping method
					if( ( $order->info['total'] - $order->info['shipping_cost'] ) < MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER ) { //if the discount lowers the total below the free shipping limit
						return true;
					}
				}
			}
			return false;
		}

		function is_errors() {
			if( count( $this->error_message ) > 0 ) return true;
			return false;
		}

		function calculate_discount( $product, $product_count ) {
			//if there's a maximum order amount to apply the discount to, determine the percentage of this product's final price we should apply the discount to
			$max_applied_percentage = ( $this->coupon['coupons_max_order'] == 0 ? '1.00' : $this->coupon['coupons_max_order'] / ( tep_add_tax( $product['final_price'] * $product['qty'], $product['tax'] ) * $product_count ) );
		  $applied_discount = tep_add_tax( $product['final_price'] * $max_applied_percentage * $this->coupon['coupons_discount_percent'], $product['tax'] ) * $product['qty'];
		  //don't allow the discount amount to be more than the product price
		  if( $applied_discount > ( tep_add_tax( $product['final_price'], $product['tax'] ) * $product['qty'] ) ) $applied_discount = tep_add_tax( $product['final_price'], $product['tax'] ) * $product['qty'];
			return $applied_discount;
		}

	}
?>