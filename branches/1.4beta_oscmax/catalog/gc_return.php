<?php
/*
  $Id: gc_return.php,v 0.1 2007/04/20 ropu $
  Converted to BTS/osCMax format - michael_s 2007/08/01
  Part of the Google Checkout Module
  */

  include_once('includes/application_top.php');
  $products = tep_db_input(implode(',', explode(',', !empty($HTTP_GET_VARS['products_id'])?$HTTP_GET_VARS['products_id']:'-1')));


// LINE ADDED: MOD - Added for Dynamic MoPics v3.000
  require(DIR_WS_FUNCTIONS . 'dynamic_mopics.php');
  $product_check_query = tep_db_query("select count(*) as total from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_status = '1' and p.products_id = '" . (int)$HTTP_GET_VARS['products_id'] . "' and pd.products_id = p.products_id and pd.language_id = '" . (int)$languages_id . "'");
  $product_check = tep_db_fetch_array($product_check_query);



  require(DIR_WS_LANGUAGES . $language . '/' . FILENAME_GC_RETURN);

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_GC_RETURN, '', 'SSL'));
  //$breadcrumb->add(NAVBAR_TITLE_2, tep_href_link(FILENAME_GC_RETURN, '', 'SSL'));

  $content = CONTENT_GC_RETURN;
  $javascript = $content . '.js.php';

  include (bts_select('main', $content_template)); // BTSv1.5

  require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
