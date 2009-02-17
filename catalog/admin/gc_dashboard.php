<?php
/*
  Copyright (C) 2009 Google Inc.

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

/**
 * Google Checkout v1.5.0
 * $Id$
 * 
 * Dashboard page for Google Checkout configuration.
 * 
 * @author Ed Davisson (ed.davisson@gmail.com)
 */
 
require_once('includes/application_top.php');

require_once(DIR_FS_CATALOG . '/googlecheckout/library/configuration/option_renderer.php');
require_once(DIR_FS_CATALOG . '/googlecheckout/library/configuration/google_options.php');

$options = new GoogleOptions();
$option_renderer = new GoogleOptionRenderer();

// If this was an update, parse the results and update.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  foreach ($options->getAllOptions() as $option) {
  	$key = $option->getKey();
    if ($option->getOptionType() == "carrier_calculated_shipping"
        || $option->getOptionType() == "merchant_calculated_shipping") {
      $all_values = array();
    	foreach ($_POST as $a => $b) {
    		if (strpos($a, $key) === 0) {
          if ($b != '') {
    			  $all_values[] = $b;
          }
    		}
    	}
      $option->setValue(join(", ", $all_values));
    } else if ($option->getOptionType() == "boolean") {
    	$option->setValue(!is_null($_POST[$key]));
    } else if (!is_null($_POST[$key])) {
      $value = $_POST[$key];
      $option->setValue($value);
    }
  }
  
  // Redirect to this page via GET.
  header('Location: ' . tep_href_link($_SERVER['PHP_SELF']));
}

?>

<html>
<head>
  <title>Google Checkout Module Dashboard</title>
  <script type="text/javascript" 
          src="../googlecheckout/library/configuration/shipping_options.js"/>
  <link rel="stylesheet" type="text/css"
          href="../googlecheckout/library/configuration/dashboard.css"/>
</head>
<body>
  <div class="container">
    <span class="pagetitle">Google Checkout Module Dashboard</span><br/>
    <span class="pagedescription">This page contains additional configuration options for the Google Checkout osCommerce module.</span><br/>
    <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
      <input type="hidden" name="isupdate" value="true"/>
      <table class="config">
        <tr class="section"><td colspan="2">Recommended Options</td></tr>
        <?php
          foreach ($options->getRecommendedOptions() as $option) {
            echo($option_renderer->render($option));
          }
        ?>
        <tr class="section"><td colspan="2">Shipping Options</td></tr>
        <?php
          foreach ($options->getShippingOptions() as $option) {
            echo($option_renderer->render($option));
          }
        ?>
        <tr class="section"><td colspan="2">Rounding Options</td></tr>
        <?php
          foreach ($options->getRoundingOptions() as $option) {
            echo($option_renderer->render($option));
          }
        ?>
        <tr class="section"><td colspan="2">Other Options</td></tr>
        <?php
          foreach ($options->getOtherOptions() as $option) {
            echo($option_renderer->render($option));
          }
        ?>
      </table>
      <input type="submit" value="Save"/>
    </form>
  </div>
</body>
</html>
