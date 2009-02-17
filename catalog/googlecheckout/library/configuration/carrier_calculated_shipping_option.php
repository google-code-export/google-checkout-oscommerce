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

require_once(DIR_FS_CATALOG . '/googlecheckout/library/configuration/google_configuration.php');
require_once(DIR_FS_CATALOG . '/includes/modules/payment/googlecheckout.php');

/**
 * Google Checkout v1.5.0
 * $Id$
 * 
 * Option for carrier calculated shipping configuration.
 * 
 * TODO(eddavisson): This is kind of a mess.
 * 
 * @author Ed Davisson (ed.davisson@gmail.com)
 */
class GoogleCarrierCalculatedShippingOption /* implements GoogleOptionInterface */ {
  
  var $type = "carrier_calculated_shipping";
  
  var $title;
  var $description;  
  var $database_key;
  
  var $google_configuration;
  
  function GoogleCarrierCalculatedShippingOption($title, $description, $database_key) {
    $this->title = $title;
    $this->description = $description;
    $this->database_key = $database_key;
    
    $this->google_configuration = new GoogleConfiguration();
  //$this->google_configuration->setDefault($this->database_key, $default);
  }

  function getOptionType() {
    return $this->type;
  }
  
  function getKey() {
  	return $this->database_key;
  }
  
  function getTitle() {
    return $this->title;
  }
  
  function getDescription() {
    return $this->description;
  }  
  
  function getValue() {
    return $this->google_configuration->getValue($this->database_key);
  }
  
  function setValue($value) {
    $this->google_configuration->setValue($this->database_key, $value);
  }
  
  function getHtml() {
    // Current values.
    $db_value = $this->getValue();
    $key_values = explode(", ", $db_value);
    
    // Set up table and header row.
    $html = '<table class="ccs">';
    $html .= '<tr class="head">';
    $html .= '<td>Default</td>';
    $html .= '<td>Fixed</td>';
    $html .= '<td>Variable</td>';
    $html .= '<td>Method</td>';   
    $html .- '</tr>';
    
    // Iterate through shipping methods.
    $google_checkout = new googlecheckout();
    foreach ($google_checkout->cc_shipping_methods_names as $code => $name) {
      foreach ($google_checkout->cc_shipping_methods[$code] as $type => $methods) {
        if (is_array($methods) && !empty($methods)) {
          foreach ($methods as $method => $method_name) {
            $key = $code . $method . $type;
            $value = $this->compare($key, $key_values, "_CSS:", '1.00|0|0');
            $values = explode('|', $value);
            
            $html .= '<tr>';
            
            // Default.
            $html .= '<td>';
            $html .= DEFAULT_CURRENCY . ' ' . $this->getInput($key, 0, $values[0]);
            $html .= '</td>';  
            
            // Fixed.
            $html .= '<td>';
            $html .= DEFAULT_CURRENCY . ' ' . $this->getInput($key, 1, $values[1]);
            $html .= '</td>';
            
            // Variable.
            $html .= '<td>';
            $html .= $this->getInput($key, 2, $values[2]) . ' %';
            $html .= '</td>';
            
             // Method name.
            $html .= '<td>' . $method_name . '</td>';           
            
            // Hidden concatenation.
            $input = '<input';
            $input .= ' type="hidden"';
            $input .= ' size="60"';
            $input .= ' id="hidden_' . $key . '"';
            $input .= ' name="' . $this->getKey() . $key . '"';
            $input .= ' value="' . $key .  '_CSS:' . $value . '"';
            $input .= '/>';
            $html .= $input;
            
            $html .= '</tr>';
          }
        }
      }
    }
    $html .= '</table>';
    return $html;
  }
  
  /**
   * Get the html for a single input.
   */
  function getInput($key, $position, $value) {
    $input = '<input';
    $input .= ' ' . $this->getHandlers($key, 2);
    $input .= ' size="6"';
    $input .= ' type="text"';
    $input .= ' name="null"';
    $input .= ' value="' . $value . '"';
    $input .= '/>';
    return $input;
  } 
  
  /**
   * Get text for the javascript handlers given the input
   * key and position.
   */
  function getHandlers($key, $position) {
    $js = '';
  	$js .= 'onBlur="ccs_blur(this';
    $js .= ', \'' . $key . '\'';
    $js .= ', \'hid_' . $key . '\'';
    $js .= ', ' . $position;
    $js .= ')" ';
    $js .= 'onFocus="ccs_focus(this';
    $js .= ', \'' . $key . '\'';
    $js .= ', \'hid_' . $key . '\'';
    $js .= ', ' . $position;
    $js .= ')"';
    return $js;
  }
  
  /**
   * TODO(eddavisson): This is bizarre, but I'm afraid to change it.
   */
  function compare($key, $data, $sep="_VD:", $default_value='1') {
  	foreach ($data as $value) {
  		list($key2, $value2) = explode($sep, $value);
      if ($key == $key2) {
      	return $value2;
      }
  	}
    return $default_value;
  }

}