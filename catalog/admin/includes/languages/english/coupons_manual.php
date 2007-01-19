<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>How to use the Discount Coupon Codes Module</title>
<style type="text/css">
<!--
.tableBorder {
	border: 1px solid #333333;
}
body {
	font-family: Tahoma, "Trebuchet MS", Verdana, Arial, sans-serif;
	font-size: 10pt;
}
.code {
	font-family: "Courier New", Courier, mono;
	font-size: 10pt;
}
h3 {
	font-family: Tahoma, "Trebuchet MS", Verdana, Arial, sans-serif;
	font-size: 14pt;
	font-weight: bolder;
	font-variant: normal;
	border-bottom-width: 1px;
	border-bottom-style: solid;
	border-bottom-color: #333333;
	border-left-width: 10px;
	border-left-style: solid;
	border-left-color: #333333;
	padding-left: 10px;
	margin-top: 50px;
}
h2 {
	font-family: Tahoma, "Trebuchet MS", Verdana, Arial, sans-serif;
	font-size: 16pt;
	font-weight: bolder;
	color: #99CCCC;
	background-color: #333333;
	text-align: center;
	padding: 5px;
}
h4 {
	font-family: Tahoma, "Trebuchet MS", Verdana, Arial, sans-serif;
	font-size: 14pt;
	font-weight: bolder;
	font-style: italic;
}
li {
	margin-bottom: 10px;
}
.notice {
	background-color: #BFDFFF;
	padding: 2px;
	border: 1px solid #666666;
	margin: 20px;
	width: 95%;
}
-->
</style>
</head>

<body>
<h2>How to use the Discount Coupon Codes Module</h2>
<h3>Contents:</h3>
<ul>
  <li><a href="#configure">Configuring the Order Total Module</a></li>
  <li><a href="#creating">Creating Discount Coupon Codes</a>
    <ul>
      <li><a href="#fields">Explanation of Coupon Fields</a>
      </li>
    </ul>
  </li>
  <ul>
    <li> <a href="#percentage_discounts">How to create percentage discounts</a></li>
    <li><a href="#fixed_discounts"> How to create fixed discounts</a></li>
  </ul>
  <li><a href="#problems">Common Problems</a></li>
  <li><a href="#support">Support</a></li>
</ul>
<h3><a name="configure"></a>Configuring the Order Total Module</h3>
 <p>To configure the Discount Coupon Codes module, go to <strong>Modules
   &gt; Order Total &gt; Discount Coupon</strong> in the admininistration section
   of your website. Click the <strong>Edit</strong> button on the right side
   of the screen to edit the configuration options. Explanations for each option
   are provided below:</p>
 <ul><li><strong>Display Discount Coupon</strong>     <br>
    Set this option to true to use the discount coupons
   module. If it is set to
    false, the Discount Coupon Code box will not appear on the
   checkout payment
    page.    </li>
   <li><strong>Sort Order</strong>     <br>
     Set this to a UNIQUE value. Each order total
    module must have a unique sort
     order, or they will not display correctly.    </li>
   <li><strong>Display Discount with Minus (-) Sign</strong>     <br>
     Set this option to true to have the discount
    display with a minus sign in
     the order confirmation, invoice, etc. If this
    is set to false, the discount
     line will simply display the discount amount. </li>
   <li><strong>Display Subtotal with Applied Discount</strong>     <br>
     Set this option to true to display the order
    subtotal with the discount
     applied. If this option is set to true, you
    will want to set the sort order
     for the discount coupons so that it displays
    before the order subtotal line,
     like so:
       <br>
       <table border="0" align="center" cellpadding="0" cellspacing="0" class="tableBorder">
         <tr>
           <td align="right">Item Doodle x 1:&nbsp;&nbsp;&nbsp;</td>
           <td>$25.00</td>
         </tr>
         <tr>
           <td align="right">Discount Coupon XYZ Applied:&nbsp;&nbsp;&nbsp;</td>
           <td>-$5.00</td>
         </tr>
         <tr>
           <td align="right">Subtotal:&nbsp;&nbsp;&nbsp;</td>
           <td>$20.00</td>
         </tr>
         <tr>
           <td align="right">Tax:&nbsp;&nbsp;&nbsp;</td>
           <td>$1.39</td>
         </tr>
         <tr>
           <td align="right">Total:&nbsp;&nbsp;&nbsp;</td>
           <td>$21.39</td>
         </tr>
       </table>
         If this option is set to false, the discount
 will not be applied to the
  order subtotal. In this case, you will want to
 set the sort order for the
  discount coupons so that it displays after the
 order subtotal line:
         <br>
         <table border="0" align="center" cellpadding="0" cellspacing="0" class="tableBorder">
           <tr>
             <td align="right">Item Doodle x 1:&nbsp;&nbsp;&nbsp;</td>
             <td>$25.00</td>
           </tr>
           <tr>
             <td align="right">Subtotal:&nbsp;&nbsp;&nbsp;</td>
             <td>$25.00</td>
           </tr>
           <tr>
             <td align="right">Discount Coupon XYZ Applied:&nbsp;&nbsp;&nbsp;</td>
             <td>-$5.00</td>
           </tr>
           <tr>
             <td align="right">Tax:&nbsp;&nbsp;&nbsp;</td>
             <td>$1.39</td>
           </tr>
           <tr>
             <td align="right">Total:&nbsp;&nbsp;&nbsp;</td>
             <td>$21.39</td>
           </tr>
         </table>
         <br>
         <span class="notice"><strong>PLEASE NOTE:</strong> If you have prices
                 set to display with taxes, then the order subtotal will
   always be displayed with the discount applied. </span></li>
   <li><strong>Random Code Length</strong>    <br>
     If you do not provide a code when creating a
   coupon, the discount coupons
    module will create a random string of letters
   and numbers for you. This
    setting controls the length of randomly
   generated codes. </li>
   <li><strong>Display Discount Total Lines for Each Tax Group?</strong> <br>
Discount coupons are applied to each item separately, in order to properly calculate
  tax if there are multiple tax groups (local and state taxes, for example).
  Set this to true to display the discount applied to each tax group. Set this
  to false to combine the discount and display as one order total line. </li>
   <li><b>Use the language file to format display string?</b><br>
     When you have multiple languages and want the order total line for the discount
       to appear in the language selected by the customer, you will need to set
       this option to true and edit the includes/languages/<em>your_language</em>/modules/order_total/ot_discount_coupon.php
       file. If you use only one language, you can set this to false and use
       the <strong>Display Format for Order Total Line</strong> configuration
       field to format the order total line.  If this field is set to true, then the value in the <strong>Display Format for Order Total Line</strong> configuration
       field will be ignored.</li>
   <li><strong>Display Format for Order Total Line</strong>    <br>
     Use this setting to change how the discount
   coupons order total line is
    formatted and displayed. You can insert the
   variables to display dynamic
    data, such as the coupon code used or the
   discount percent.  The list of available variables:
       <br>
     [code]
     <br>
     [coupon_desc]
     <br>
     [percent_discount]
     <br>
     [min_order]
     <br>
     [number_available]
     <br>
   [tax_desc] </li>
 </ul>
 <h3><a name="creating"></a>Creating Discount Coupon Codes</h3>
 <p>Log in to your admin section and go to <strong>Catalog   &gt; Discount
   Coupons</strong>. Click the
   <strong>New Coupon</strong> button to create a new Discount Coupon Code. The
   only field that is required is the percent discount field. </p>
 <p>Once you have created a discount coupon code, you may distribute it in any
   format. The only information a customer will need to use a coupon is the code
   you specified when creating the coupon. You can send out codes in newsletters,
   order confirmations, etc. </p>
 <p> Discounts are displayed as order total lines in the customer's checkout
 confirmation screen and order history screen, and in all admin order screens.
</p>
 <p>Discounts are applied BEFORE tax. </p>
 <h4><a name="fields"></a>Explanation of Coupon Fields: </h4>
 <ul>
   <li> <strong>Coupon Code</strong>     <br>
     This is the code you will give to customers
    which they enter during the
     checkout process. It can be any alphanumeric
    character, up to 32
     characters long.
If you leave this field blank, a randomn alphanumeric code will be generated
       for you. This field cannot be edited for existing
       coupons. </li>
   <li><strong>Description</strong>     <br>
     This is a short description field to help you
    administer your coupons.
     The contents of this field are not displayed to the customer unless you
       choose to display it in the order total format. See &quot;Display Format for
       the Order Total Line&quot; in <a href="#configure">Configuring the Order
       Total Module</a>.</li>
   <li><strong>Discount Percent</strong>     <br>
     The percentage discount to apply to the order.
    Enter this as a decimal
     amount, eg .15 rather than 15 or 15%. This field cannot be edited for existing
     coupons. </li>
   <li><strong>Fixed Discount</strong><br>
     You may enter a fixed discount amount instead of a percentage discount along
       with an order minimum to have the module calculate the proper percentage
       discount to acheive the given fixed discount amount. Enter the fixed discount
       amount without any currency marks, eg 5 instead of $5. Leave this blank
       to use a simple percentage discount. This
       field cannot be edited for existing coupons.</li>
   <li><strong>Start Date</strong>     <br>
     Enter a start date if you wish to limit the when
    customers may begin using
     this coupon. You may leave this field empty to
    allow customers to use the
     coupon as soon as it is created.          </li>
   <li><strong>End Date</strong>     <br>
     Enter an ending date if you wish to limit how
    long customers may use this
     coupon. You may leave this field empty to allow
    customers to use the
     coupon as long as they wish.            </li>
   <li><strong>Max Use</strong>     <br>
     Enter the maximum number of times each individual customer may
    use this coupon. If you
     do not enter a value, or you enter 0, the
    customer will be allowed to use
     this coupon an unlimited number of times.              </li>
   <li><strong>Min Order</strong>     <br>
     Enter the minimum order subtotal a customer must
    have before he or she may
     use this coupon. If you do not enter a value,
    or you enter 0, there will
     be no order minimum required for this coupon. This field cannot be edited
     for existing coupons.                </li>
   <li><strong>Max Order</strong>     <br>
     Enter the maximum order subtotal to which this
    discount should be applied.
     You may use this field to set an upper limit on
    discounts. If an order subtotal is greater than the Max Order field, then
       the Max Order field will be used as the basis for calculating the discount.
       If you do not enter a value, or you enter 0, the discount will be applied
       to the entire order subtotal amount. This field cannot be edited for existing
       coupons.          </li>
   <li><strong>Number Available</strong>     <br>
     Enter the global number of coupons available.
    You may use this to limit the      number of times the coupon
can be used for the entire store. This may be used to
     give a discount to the first 200 customers on a
    particular day, for example. </li>
 </ul>
 <h4><a name="percentage_discounts"></a>How to Create Percentage Discounts</h4>
 <p>Percentage discounts require only a code and a discount percentage. All other
   fields are optional.</p>
 <h4><a name="fixed_discounts"></a>How to Create Fixed Discounts</h4>
 <p class="notice"><strong>PLEASE NOTE:</strong> Due to the way osCommerce handles
   order total calculation and the way that Discount Coupon Codes applies a discount
   to order totals, fixed discounts have the possibility of being off by small
   fractions. This can possibly result in a fixed discount of $5.00 being displayed
   in the order total line as $4.99, for example. The frequency of this happening
   will be very low. The reason has to do with <em>when</em> the Discount Coupon
   Codes contribution calculates discounts. The calculation happens during the
   order object creation, when final total values are not yet known, so the discount
   is applied incrementally. There are pros and cons to this method. Some other
   Discount Coupon modules calculate discounts after the order object has been
   created and so may handle fixed discounts with a higher degree of accuracy.
   If the possibility of fractional rounding errors is unacceptable to you or
   your customers, I recommend trying one of the other discount coupon contributions. </p>
 <p>Fixed discounts are created by setting an order minimum (the minimum amount
   a customer must have in his or her cart before being eligeable for the discount),
   a percentage discount, and an order maximum (the maximum order amount to apply
   the discount to). If the order maximum is the same as the order minimum, then
   the percentage discount for every order using this code will be fixed.</p>
 <p>Suppose you wish to allow a $5.00 discount on orders $25 or more. By setting
   the order minimum and maximum both to $25, you ensure the applied discount
   will be the same for all orders using this discount coupon code. If the discount
   percent is 20%, the discount will always be $5.00.</p>
 <p>You may enter an order minimum and a fixed discount amount when creating
   coupons to have the Discount Coupon Codes module automatically calculate
   the correct discount percentage for you.</p>
 <h3><a name="problems"></a>Common Problems</h3>
 <ul>
   <li><strong>Discount coupon does not display in order subtotal during checkout<br>
    confirmation.
      </strong><br>
     Ensure the discount coupon has a unique sort order. Set this in <br>
     Modules &gt; Order Total.</li>
   <li><strong>     Warning: include(***file1***) [function.include]: failed
       to open stream: No <br>
   such file or directory in ***file2*** on line ***line#***<br>
   </strong>Ensure that you have uploaded ***file1*** to the correct
   directory.</li>
   <li><strong>&quot;Discount Coupon&quot; does not appear in the list of Order
       Total modules under<br>
   Modules &gt; Order Total in the admin section.<br>
   </strong>
     Ensure that you have uploaded the module files contained in the contribution <br>
     package:<br>
     catalog/includes/modules/order_total/ot_discount_coupon.php<br>
     catalog/includes/languages/english/modules/order_total/ot_discount_coupon.php</li>
   <li><strong>     The Discount Applied order total line shows an incorrect
       discount amount for<br>
    the order subtotal. (Only happens on some orders and amount is off by
    no more <br>
   than a few cents.)<br>
   </strong>
     There isn't really one. The discount is applied before tax, and OSC allows <br>
     for multiple tax groups in each order. This means the discount percentage<br>
     must be applied to each line item in the order, taking into account the
     tax <br>
     rate for that item. Rounding can then cause the Discount Applied line
     to be a <br>
     few cents off of the actual discount percentage for the Sub-Total line.<br>
     <br>
     The discount is not actually taken from the order Sub-Total, even though
      it <br>
     may appear as though it is. If you have configured your store to display<br>
     prices with taxes and/or you have configured your Discount Coupon Codes
     module to <br>
     display the order Sub-total without the discount applied, then your likelihood<br>
     of running into this situation is slightly higher.</li>
   <li><strong>Fatal error: Cannot redeclare class discount_coupon in 
   ***path to shop***/includes/classes/discount_coupon.php on line 14.<br>
   </strong>Edit the file includes/classes/discount_coupon.php and comment out the line<br>
   <span class="code">require_once( DIR_FS_CATALOG.DIR_WS_CLASSES.'discount_coupon.php' ); </span></li>
 </ul>
 <h3><a name="support"></a>Support</h3>
 <p>You can find support for this contribution at <a href="http://forums.oscommerce.com/index.php?showtopic=213019">http://forums.oscommerce.com/index.php?showtopic=213019</a> (OSCommerce
   User Forums). Please read through the previous postings to see if your problem
   or question has already been answered. </p>
</body>
</html>