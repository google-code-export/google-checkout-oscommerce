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
 
  /**
  * original createRandomCoupon() contributed by Cubez
  */
  function createRandomCoupon() {
    $chars = "ABCDEFGHJKLMNPQRTUVWXYZ023456789";
    srand( (double) microtime() * 1000000 );
    $pass = '';
    for( $i = 0; $i < MODULE_ORDER_TOTAL_DISCOUNT_COUPON_RANDOM_CODE_LENGTH; $i++ ) {
        $pass .= substr( $chars, ( rand() % 33 ), 1 );
    }
    return $pass;
  }

	/**
	* Returns a formatted date from a string based on a given format
	*
	* Supported formats
	*
	* %Y - year as a decimal number including the century
	* %m - month as a decimal number (range 1 to 12)
	* %d - day of the month as a decimal number (range 1 to 31)
	*
	* %H - hour as decimal number using a 24-hour clock (range 0 to 23)
	* %M - minute as decimal number
	* %s - second as decimal number
	* %u - microsec as decimal number
	* @param string date  string to convert to date
	* @param string format expected format of the original date
	* @return string rfc3339 w/o timezone YYYY-MM-DD YYYY-MM-DDThh:mm:ss YYYY-MM-DDThh:mm:ss.s
	*/
	function parseDate( $date, $format ) {
		// Builds up date pattern from the given $format, keeping delimiters in place.
		if( !preg_match_all( "/%([YmdHMsu])([^%])*/", $format, $formatTokens, PREG_SET_ORDER ) ) {
			return false;
		}
		foreach( $formatTokens as $formatToken ) {
			$delimiter = preg_quote( $formatToken[2], "/" );
			if($formatToken[1] == 'Y') {
				$datePattern .= '(.{1,4})'.$delimiter;
			} elseif($formatToken[1] == 'u') {
				$datePattern .= '(.{1,5})'.$delimiter;
			} else {
				$datePattern .= '(.{1,2})'.$delimiter;
			}
		}

		// Splits up the given $date
		if( !preg_match( "/".$datePattern."/", $date, $dateTokens) ) {
			return false;
		}
		$dateSegments = array();
		for($i = 0; $i < count($formatTokens); $i++) {
			$dateSegments[$formatTokens[$i][1]] = $dateTokens[$i+1];
		}

		// Reformats the given $date into rfc3339
		if( $dateSegments["Y"] && $dateSegments["m"] && $dateSegments["d"] ) {
			if( ! checkdate ( $dateSegments["m"], $dateSegments["d"], $dateSegments["Y"] ) ) return false;
			$dateReformated = str_pad($dateSegments["Y"], 4, '0', STR_PAD_LEFT)."-".str_pad($dateSegments["m"], 2, '0', STR_PAD_LEFT)."-".str_pad($dateSegments["d"], 2, '0', STR_PAD_LEFT);
		} else {
			return false;
		}
		if( $dateSegments["H"] && $dateSegments["M"] ) {
			$dateReformated .= "T".str_pad($dateSegments["H"], 2, '0', STR_PAD_LEFT).':'.str_pad($dateSegments["M"], 2, '0', STR_PAD_LEFT);

			if( $dateSegments["s"] ) {
				$dateReformated .= ":".str_pad($dateSegments["s"], 2, '0', STR_PAD_LEFT);
				if( $dateSegments["u"] ) {
					$dateReformated .= '.'.str_pad($dateSegments["u"], 5, '0', STR_PAD_RIGHT);
				}
			}
		}

		return $dateReformated;
	}

  require('includes/application_top.php');

  $action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

  if (tep_not_null($action)) {
    switch ($action) {
      case 'insert':
      	//some error checking:
      	//if entered both percent and fixed discount
      	if( !empty( $HTTP_POST_VARS['coupons_discount_percent'] ) && !empty( $HTTP_POST_VARS['coupons_discount_fixed'] ) ) {
      		$messageStack->add(ERROR_DISCOUNT_COUPONS_PERCENT_AND_FIXED, 'error');
      		$action = 'new';
      	//if entered fixed discount with no order minimum
				} else if( !empty( $HTTP_POST_VARS['coupons_discount_fixed'] ) && empty( $HTTP_POST_VARS['coupons_min_order'] ) ) {
					$messageStack->add(ERROR_DISCOUNT_COUPONS_FIXED_NO_MIN, 'error');
					$action = 'new';
				} else {
	        if( !empty( $HTTP_POST_VARS['coupons_discount_fixed'] ) && !empty( $HTTP_POST_VARS['coupons_min_order'] ) ) {
	        	$HTTP_POST_VARS['coupons_max_order'] = $HTTP_POST_VARS['coupons_min_order'];
						$HTTP_POST_VARS['coupons_discount_percent'] = $HTTP_POST_VARS['coupons_discount_fixed'] / $HTTP_POST_VARS['coupons_min_order'];
					}
	        tep_db_query($sql = "insert into " . TABLE_DISCOUNT_COUPONS . " (
        				coupons_id,
        				coupons_description,
        				coupons_discount_percent,
        				coupons_date_start,
        				coupons_date_end,
								coupons_max_use,
								coupons_min_order,
								coupons_max_order,
								coupons_number_available)
        				values ('" .
        				( !empty( $HTTP_POST_VARS["coupons_id"] ) ? tep_db_input( $HTTP_POST_VARS["coupons_id"] ) : createRandomCoupon() ). "', '" .
        				tep_db_input( $HTTP_POST_VARS['coupons_description'] ) . "', '" .
        				tep_db_input( $HTTP_POST_VARS['coupons_discount_percent'] ) ."', " .
        				( !empty( $HTTP_POST_VARS['coupons_date_start'] ) ? '"'.parseDate( $HTTP_POST_VARS['coupons_date_start'], DATE_FORMAT_SHORT ).'"' : 'null' ). ", ".
        				( !empty( $HTTP_POST_VARS['coupons_date_end'] ) ? '"'.parseDate( $HTTP_POST_VARS['coupons_date_end'], DATE_FORMAT_SHORT ).'"' : 'null' ). ", ".
        				( !empty( $HTTP_POST_VARS['coupons_max_use'] ) ? (int)$HTTP_POST_VARS['coupons_max_use'] : 0 ).", ".
								( !empty( $HTTP_POST_VARS['coupons_min_order'] ) ? $HTTP_POST_VARS['coupons_min_order'] : 0 ).", ".
								( !empty( $HTTP_POST_VARS['coupons_max_order'] ) ? $HTTP_POST_VARS['coupons_max_order'] : 0 ).", ".
								( !empty( $HTTP_POST_VARS['coupons_number_available'] ) ? (int)$HTTP_POST_VARS['coupons_number_available'] : 0 ).")");
	        tep_redirect( tep_href_link( FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] ) );
				}
	      break;
      case 'update':
        tep_db_query($sql = "update " . TABLE_DISCOUNT_COUPONS . " set
        			coupons_description = '" . tep_db_input( $HTTP_POST_VARS['coupons_description'] ) . "',
        			coupons_date_start = " . ( !empty( $HTTP_POST_VARS['coupons_date_start'] ) ? '"'.parseDate( $HTTP_POST_VARS['coupons_date_start'], DATE_FORMAT_SHORT ).'"' : 'null' ) . ",
        			coupons_date_end = " .( !empty( $HTTP_POST_VARS['coupons_date_end'] ) ? '"'.parseDate( $HTTP_POST_VARS['coupons_date_end'], DATE_FORMAT_SHORT ).'"' : 'null' ). ",
							coupons_max_use = " .( !empty( $HTTP_POST_VARS['coupons_max_use'] ) ? (int)$HTTP_POST_VARS['coupons_max_use'] : 0 ). ",
							coupons_number_available = " .( !empty( $HTTP_POST_VARS['coupons_number_available'] ) ? (int)$HTTP_POST_VARS['coupons_number_available'] : 0 ). "
        			where coupons_id = '" . tep_db_input( $HTTP_POST_VARS['coupons_id'] ) . "'");
        tep_redirect(tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $HTTP_POST_VARS['coupons_id']));
        break;
      case 'deleteconfirm':
        $coupons_id = tep_db_prepare_input($HTTP_GET_VARS['cID']);
        tep_db_query($sql = "delete from " . TABLE_DISCOUNT_COUPONS . " where coupons_id = '" .$coupons_id. "'");
        tep_db_query($sql = "delete from " . TABLE_DISCOUNT_COUPONS_TO_ORDERS . " where coupons_id = '" .$coupons_id. "'");
        tep_redirect(tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page']));
        break;
    }
  }
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
<title><?php echo TITLE; ?></title>
<link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
<script language="javascript" src="includes/general.js"></script>
</head>
<body marginwidth="0" marginheight="0" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" bgcolor="#FFFFFF" onload="SetFocus();">
<div id="popupcalendar" class="text"></div>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td width="<?php echo BOX_WIDTH; ?>" valign="top"><table border="0" width="<?php echo BOX_WIDTH; ?>" cellspacing="1" cellpadding="1" class="columnLeft">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </table></td>
<!-- body_text //-->
    <td width="100%" valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="specialPrice" align="right">NOTICE: <a href="<?php echo tep_href_link( DIR_WS_LANGUAGES.$language.'/'.FILENAME_DISCOUNT_COUPONS_MANUAL ).'">'.HEADING_TITLE_VIEW_MANUAL; ?></a></td>
          </tr>
        </table></td>
      </tr>
<?php
  if ( ($action == 'new') || ($action == 'edit') ) {
    $form_action = 'insert';
    if ( ($action == 'edit') && isset($HTTP_GET_VARS['cID']) ) {
      $form_action = 'update';

      $coupons_query = tep_db_query("select * from " . TABLE_DISCOUNT_COUPONS . " where coupons_id = '" . $HTTP_GET_VARS['cID'] . "'");
      $coupons = tep_db_fetch_array($coupons_query);

      $cInfo = new objectInfo($coupons);

      if( !empty( $cInfo->coupons_max_order ) && !empty( $cInfo->coupons_min_order ) && $cInfo->coupons_max_order == $cInfo->coupons_min_order ) {
				$fixed_discount = $cInfo->coupons_discount_percent * $cInfo->coupons_max_order;
			}
    } else {
      $cInfo = new objectInfo(array());
    }
?>
      <tr><form name="new_coupon" <?php echo 'action="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, tep_get_all_get_params(array('action', 'info', 'cID')) . 'action=' . $form_action, 'NONSSL') . '"'; ?> method="post"><?php if ($form_action == 'update') echo tep_draw_hidden_field('coupons_id', $HTTP_GET_VARS['cID']); ?>
        <td><br><table border="0" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_ID; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_id',$cInfo->coupons_id, 'size="10" maxlength="32"'.( $action == 'edit' ? ' disabled' : '' ) ); ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_DESCRIPTION; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_description',$cInfo->coupons_description, 'size="25" maxlength="64"'); ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_PERCENT; ?>&nbsp;</td>
            <td class="main">
<?php
		echo tep_draw_input_field('coupons_discount_percent', $cInfo->coupons_discount_percent, 'size="5" maxlength="10"'.( $action == 'edit' ? ' disabled' : '' ));
		echo TEXT_DISCOUNT_COUPONS_FIXED;
		echo tep_draw_input_field('coupons_discount_fixed', ''.$fixed_discount, 'size="5" maxlength="10"'.( $action == 'edit' ? ' disabled' : '' ));
?>
            </td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_DATE_START; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_date_start', ( !empty($cInfo->coupons_date_start) ? tep_date_short( $cInfo->coupons_date_start ) : '' ), 'size="10" maxlength="10"') ; ?></a></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_DATE_END; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_date_end', ( !empty($cInfo->coupons_date_end) ? tep_date_short( $cInfo->coupons_date_end ) : '' ), 'size="10" maxlength="10"') ; ?></a></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_MAX_USE; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_max_use', $cInfo->coupons_max_use, 'size="5" maxlength="5"'); ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_MIN_ORDER; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_min_order', $cInfo->coupons_min_order, 'size="5" maxlength="5"'.( $action == 'edit' ? ' disabled' : '' )); ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_MAX_ORDER; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_max_order', $cInfo->coupons_max_order, 'size="5" maxlength="5"'.( $action == 'edit' ? ' disabled' : '' )); ?></td>
          </tr>
          <tr>
            <td class="main" align="right" valign="top"><?php echo TEXT_DISCOUNT_COUPONS_NUMBER_AVAILABLE; ?>&nbsp;</td>
            <td class="main"><?php echo tep_draw_input_field('coupons_number_available', $cInfo->coupons_number_available, 'size="5" maxlength="5"'); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" align="right" valign="top"><br><?php echo (($form_action == 'insert') ? tep_image_submit('button_insert.gif', IMAGE_INSERT) : tep_image_submit('button_update.gif', IMAGE_UPDATE)). '&nbsp;&nbsp;&nbsp;<a href="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . (isset($HTTP_GET_VARS['cID']) ? '&cID=' . $HTTP_GET_VARS['cID'] : '')) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>'; ?></td>
          </tr>
        </table></td>
      </form></tr>
<?php
  } else {
  	require(DIR_WS_CLASSES . 'currencies.php');
    $currencies = new currencies();
?>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_DISCOUNT_COUPONS_ID; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_DISCOUNT_PERCENT; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_DATE_START; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_DATE_END; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_MAX_USE; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_MIN_ORDER; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_MAX_ORDER; ?></td>
                <td class="dataTableHeadingContent" align="left"><?php echo TEXT_INFO_NUMBER_AVAILABLE; ?></td>
                <td class="dataTableHeadingContent" align="left">&nbsp;</td>
              </tr>
<?php
    $coupons_query_raw = "select * from " . TABLE_DISCOUNT_COUPONS . " cd order by cd.coupons_date_end, coupons_date_start";
    $coupons_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $coupons_query_raw, $coupons_query_numrows);
    $coupons_query = tep_db_query($coupons_query_raw);
    while ($coupons = tep_db_fetch_array($coupons_query)) {
      if ((!isset($HTTP_GET_VARS['cID']) || (isset($HTTP_GET_VARS['cID']) && ($HTTP_GET_VARS['cID'] == $coupons['coupons_id']))) && !isset($cInfo) && (substr($action, 0, 3) != 'new')) {
	      $cInfo = new objectInfo($coupons);
	  }

      if (isset($cInfo) && is_object($cInfo) && ($coupons['coupons_id'] == $cInfo->coupons_id) ) {
        echo '                  <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $cInfo->coupons_id . '&action=edit') . '\'">' . "\n";
      } else {
        echo '                  <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $coupons['coupons_id']) . '\'">' . "\n";
      }
?>
                <td class="dataTableContent" align="left"><?php echo $coupons['coupons_id'].' <small>'.( !empty( $coupons['coupons_description'] ) ? '( '.$coupons['coupons_description'].' )' : '' ) .'</small>'; ?></td>
                <td class="dataTableContent" align="left"><?php echo ($coupons['coupons_discount_percent'] * 100).'%'; ?></td>
                <td class="dataTableContent" align="left"><?php echo !empty( $coupons['coupons_date_start'] ) ? tep_date_short( $coupons['coupons_date_start'] ) : 'unlimited'; ?></td>
                <td class="dataTableContent" align="left"><?php echo !empty( $coupons['coupons_date_end'] ) ? tep_date_short( $coupons['coupons_date_end'] ) : 'unlimited'; ?></td>
                <td class="dataTableContent" align="left"><?php echo ( $coupons['coupons_max_use'] != 0 ? $coupons['coupons_max_use'] : 'unlimited' ); ?></td>
                <td class="dataTableContent" align="left"><?php echo ( $coupons['coupons_min_order'] != 0 ? $currencies->format( $coupons['coupons_min_order'] ) : 'unlimited' ); ?></td>
                <td class="dataTableContent" align="left"><?php echo ( $coupons['coupons_max_order'] != 0 ? $currencies->format( $coupons['coupons_max_order'] ) : 'unlimited' ); ?></td>
                <td class="dataTableContent" align="left"><?php echo ( $coupons['coupons_number_available'] != 0 ? $coupons['coupons_number_available'] : 'unlimited' ); ?></td>
                <td class="dataTableContent" align="left"><?php if (isset($cInfo) && is_object($cInfo) && ($coupons['coupons_id'] == $cInfo->coupons_id)) { echo tep_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $coupons['coupons_id']) . '">' . tep_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>&nbsp;</td>
      </tr>
<?php
    }
?>
              <tr>
                <td colspan="7"><table border="0" width="100%" cellpadding="0"cellspacing="2">
                  <tr>
                    <td class="smallText" align="right"><?php echo $coupons_split->display_links($coupons_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page']); ?></td>
                  </tr>
<?php
  if (empty($action)) {
?>
                  <tr>
                    <td align="right"><?php echo '<a href="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&action=new') . '">' . tep_image_button('button_new_coupon.gif', IMAGE_NEW_COUPON) . '</a>'; ?></td>
                  </tr>
<?php
  }
?>
                </table></td>
              </tr>
            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'delete':
      $heading[] = array('text' => '<b>' . TEXT_INFO_HEADING_DELETE_DISCOUNT_COUPONS . '</b>');

      $contents = array('form' => tep_draw_form('coupons', FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $cInfo->coupons_id . '&action=deleteconfirm'));
      $contents[] = array('text' => TEXT_INFO_DELETE_INTRO);
      $contents[] = array('text' => '<br><b>' . $cInfo->coupons_id . '</b>');
      $contents[] = array('align' => 'center', 'text' => '<br>' . tep_image_submit('button_delete.gif', IMAGE_DELETE) . '&nbsp;<a href="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $cInfo->coupons_id) . '">' . tep_image_button('button_cancel.gif', IMAGE_CANCEL) . '</a>');
      break;
    default:
      if (is_object($cInfo)) {
        $heading[] = array('text' => '<b>' . $cInfo->coupons_id . '</b>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $cInfo->coupons_id . '&action=edit') . '">' . tep_image_button('button_edit.gif', IMAGE_EDIT) . '</a> <a href="' . tep_href_link(FILENAME_DISCOUNT_COUPONS, 'page=' . $HTTP_GET_VARS['page'] . '&cID=' . $cInfo->coupons_id . '&action=delete') . '">' . tep_image_button('button_delete.gif', IMAGE_DELETE) . '</a>' );
        $contents[] = array('text' => '<br>' . TEXT_INFO_DISCOUNT_PERCENT . ' ' . (  $cInfo->coupons_discount_percent * 100 ) . '%' );
        $contents[] = array('text' => '' . TEXT_INFO_DATE_START . ' ' . ( !empty( $cInfo->coupons_date_start ) ? tep_date_short( $cInfo->coupons_date_start ) : 'unlimited' ) );
        $contents[] = array('text' => '' . TEXT_INFO_DATE_END . ' ' . ( !empty( $cInfo->coupons_date_end ) ? tep_date_short( $cInfo->coupons_date_end ) : 'unlimited' ) );
        $contents[] = array('text' => '' . TEXT_INFO_MAX_USE . ' ' . ( $cInfo->coupons_max_use != 0 ? $cInfo->coupons_max_use : 'unlimited' ) );
        $contents[] = array('text' => '' . TEXT_INFO_MIN_ORDER . ' ' . ( $cInfo->coupons_min_order != 0 ? $currencies->format( $cInfo->coupons_min_order ) : 'unlimited' ) );
        $contents[] = array('text' => '' . TEXT_INFO_MAX_ORDER . ' ' . ( $cInfo->coupons_max_order != 0 ? $currencies->format( $cInfo->coupons_max_order ) : 'unlimited' ) );
        $contents[] = array('text' => '' . TEXT_INFO_NUMBER_AVAILABLE . ' ' . ( $cInfo->coupons_number_available != 0 ? $cInfo->coupons_number_available : 'unlimited' ) );
      }
      break;
  }
  if ( (tep_not_null($heading)) && (tep_not_null($contents)) ) {
    echo '            <td width="25%" valign="top">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
}
?>
          </tr>
        </table></td>
      </tr>
    </table></td>
<!-- body_text_eof //-->
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>