<?php
/*
 * coupons.php
 * August 4, 2006
 * author: Kristen G. Thorson
 * ot_discount_coupon_codes version 2.0
 *
 * Released under the GNU General Public License
 *
 */

define('HEADING_TITLE', 'Discount Coupons');
define('HEADING_TITLE_VIEW_MANUAL', 'Click here to read the Discount Coupon Codes manual for help editing coupons.');

define('TEXT_DISCOUNT_COUPONS_ID', 'Coupon Code:');
define('TEXT_DISCOUNT_COUPONS_DESCRIPTION', 'Description:');
define('TEXT_DISCOUNT_COUPONS_PERCENT', 'Percent Discount:');
define('TEXT_DISCOUNT_COUPONS_FIXED', 'OR Desired Fixed Discount:');
define('TEXT_DISCOUNT_COUPONS_DATE_START', 'Start Date:');
define('TEXT_DISCOUNT_COUPONS_DATE_END', 'End Date:');
define('TEXT_DISCOUNT_COUPONS_MAX_USE', 'Max Use:');define('TEXT_DISCOUNT_COUPONS_MIN_ORDER', 'Min Order:');
define('TEXT_DISCOUNT_COUPONS_MAX_ORDER', 'Max Order:');
define('TEXT_DISCOUNT_COUPONS_NUMBER_AVAILABLE', 'Number Available:');

define('TEXT_DISPLAY_NUMBER_OF_DISCOUNT_COUPONS', 'Items');

define('TEXT_INFO_DISCOUNT_PERCENT', 'Discount:');
define('TEXT_INFO_DATE_START', 'Start:');
define('TEXT_INFO_DATE_END', 'End:');
define('TEXT_INFO_MAX_USE', 'Max Use:');
define('TEXT_INFO_MIN_ORDER', 'Min Order:');
define('TEXT_INFO_MAX_ORDER', 'Max Order:');
define('TEXT_INFO_NUMBER_AVAILABLE', 'Available:');

define('TEXT_INFO_HEADING_DELETE_DISCOUNT_COUPONS', 'Delete Discount Coupon');
define('TEXT_INFO_DELETE_INTRO', 'Are you sure you want to delete this discount coupon?');

define('ERROR_DISCOUNT_COUPONS_PERCENT_AND_FIXED', 'You have entered both a percent and fixed discount amount.  Please enter only one or the other.');
define('ERROR_DISCOUNT_COUPONS_FIXED_NO_MIN', 'You have entered a fixed discount without an order minimum.  Please enter an order minimum.');

?>
