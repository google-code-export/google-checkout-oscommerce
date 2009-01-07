<?php
/*
  Copyright (C) 2008 Google Inc.

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
 * Functions to store configuration values (shipping options) using
 * checkboxes in the administration tool. These methods are meant to
 * be included in catalog/admin/includes/functions/general.php.
 * 
 * TODO(ed.davisson): Can we get rid of these somehow?
 */

/**
 * Carrier calculated shipping.
 * 
 * Perhaps this function should be moved to the googlecheckout
 * class; it's not very general.
 */
function gc_cfg_select_CCshipping($key_value, $key = '') {
  // Get all the available shipping methods.
  global $PHP_SELF, $language, $module_type;
  
  require_once (DIR_FS_CATALOG . 'includes/modules/payment/googlecheckout.php');
  $googlepayment = new googlecheckout();
  
  $javascript = "<script language='javascript'>
          
        function CCS_blur(valor, code, hid_id, pos) {
          var hid = document.getElementById(hid_id);
          var temp = hid.value.substring((code  + '_CCS:').length).split('|');
          valor.value = isNaN(parseFloat(valor.value))?'':parseFloat(valor.value);
          if (valor.value != '') { 
            temp[pos] = valor.value;
          } else {
            temp[pos] = 0;
            valor.value = '0';      
          }
          hid.value = code + '_CCS:' + temp[0] + '|'+ temp[1] + '|'+ temp[2];
        }
    
        function CCS_focus(valor, code, hid_id, pos) {
          var hid = document.getElementById(hid_id);
          var temp = hid.value.substring((code  + '_CCS:').length).split('|');
        //valor.value = valor.value.substr((code  + '_CCS:').length, hid.value.length);
          temp[pos] = valor.value;        
          hid.value = code + '_CCS:' + temp[0] + '|'+ temp[1] + '|'+ temp[2];        
        }
        </script>";

  $string .= $javascript;
  
  $key_values = explode(", ", $key_value);
  
  foreach($googlepayment->cc_shipping_methods_names as $CCSCode => $CCSName){
    
    $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
    $string .= "<br><b>" . $CCSName . "</b>"."\n";
    foreach($googlepayment->cc_shipping_methods[$CCSCode] as $type => $methods) {
      if (is_array($methods) && !empty($methods)) {
        $string .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'. $type .'</b><br />';            
          $string .= 'Def. Value | Fix Charge | Variable | Method Name';
        foreach($methods as $method => $method_name) {
          $string .= '<br>';
          
          // default value 
          $value = gc_compare($CCSCode . $method. $type , $key_values, "_CCS:", '1.00|0|0');
          $values = explode('|',$value);
          $string .= DEFAULT_CURRENCY . ':<input size="3"  onBlur="CCS_blur(this, \'' . $CCSCode. $method . $type . '\', \'hid_' .
                      $CCSCode . $method . $type . '\', 0);" onFocus="CCS_focus(this, \'' . $CCSCode . $method .
                      $type . '\' , \'hid_' . $CCSCode . $method . $type .'\', 0);" type="text" name="no_use' . $method . 
                      '" value="' . $values[0] . '"> ';

          $string .= DEFAULT_CURRENCY . ':<input size="3"  onBlur="CCS_blur(this, \'' . $CCSCode. $method . $type . '\', \'hid_' .
                      $CCSCode . $method . $type . '\', 1 );" onFocus="CCS_focus(this, \'' . $CCSCode . $method .
                      $type . '\' , \'hid_' . $CCSCode . $method . $type .'\', 1);" type="text" name="no_use' . $method . 
                      '" value="' . $values[1] . '"> ';

          $string .= '<input size="3"  onBlur="CCS_blur(this, \'' . $CCSCode. $method . $type . '\', \'hid_' .
                      $CCSCode . $method . $type . '\', 2 );" onFocus="CCS_focus(this, \'' . $CCSCode . $method .
                      $type . '\' , \'hid_' . $CCSCode . $method . $type .'\', 2);" type="text" name="no_use' . $method . 
                      '" value="' . $values[2] . '">% ';

          $string .= '<input size="10" id="hid_' . $CCSCode . $method . $type . '" type="hidden" name="' . $name . 
                      '" value="' . $CCSCode . $method . $type . '_CCS:' . $value . '">'."\n";      

          $string .= $method_name;
        }
      }
    }
  }
  return $string;
}

function gc_cfg_select_multioption($select_array, $key_value, $key = '') {
  for ($i = 0; $i < sizeof($select_array); $i++) {
    $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
    $string .= '<br><input type="checkbox" name="' . $name . '" value="' . $select_array[$i] . '"';
    $key_values = explode( ", ", $key_value);
    if ( in_array($select_array[$i], $key_values) ) $string .= ' CHECKED';
    $string .= '>' . $select_array[$i];
  }
  $string .= '<input type="hidden" name="' . $name . '" value="--none--">';
  return $string;
}

/**
 * Custom function to store configuration values (shipping default values).
 */
function gc_compare($key, $data, $sep="_VD:", $def_ret='1') {
  foreach($data as $value) {
    list($key2, $valor) = explode($sep, $value);
    if ($key == $key2) {
      return $valor;
    }
  }
  return $def_ret;
}

/**
 * Perhaps this function should be moved to googlecheckout 
 * class; it's not very general.
 */
function gc_cfg_select_shipping($select_array, $key_value, $key = '') {
  // Get all the available shipping methods.
  global $PHP_SELF, $language, $module_type;
  
  $module_directory = DIR_FS_CATALOG_MODULES . 'shipping/';
  
  $file_extension = substr($PHP_SELF, strrpos($PHP_SELF, '.'));
  $directory_array = array();
  if ($dir = @dir($module_directory)) {
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

  $installed_modules = array();
  $select_array = array();
  for ($i=0, $n=sizeof($directory_array); $i<$n; $i++) {
    $file = $directory_array[$i];

    include_once(DIR_FS_CATALOG_LANGUAGES . $language . '/modules/shipping/' . $file);
    include_once($module_directory . $file);

    $class = substr($file, 0, strrpos($file, '.'));
    if (tep_class_exists($class)) {
      $module = new $class;
      //echo $class;
      if ($module->check() > 0) {

        $select_array[$module->code] = array('code' => $module->code,
                             'title' => $module->title,
                             'description' => $module->description,
                             'status' => $module->check());
      }
    }
  }
  require_once (DIR_FS_CATALOG . 'includes/modules/payment/googlecheckout.php');
  $googlepayment = new googlecheckout();
  
  $ship_calcualtion_mode = (count(array_keys($select_array)) > count(array_intersect($googlepayment->shipping_support, array_keys($select_array)))) ? true : false;
  if(!$ship_calcualtion_mode) {
    return '<br/><i>'. GOOGLECHECKOUT_TABLE_NO_MERCHANT_CALCULATION . '</i>';
  }

  $javascript = "<script language='javascript'>
              
            function VD_blur(valor, code, hid_id) {
              var hid = document.getElementById(hid_id);
              valor.value = isNaN(parseFloat(valor.value))?'':parseFloat(valor.value);
              if (valor.value != '') { 
                hid.value = code + '_VD:' + valor.value;
              //valor.value = valor.value;  
              //hid.disabled = false;
              } else {   
                hid.value = code + '_VD:0';
                valor.value = '0';      
              }
        
        
            }
        
            function VD_focus(valor, code, hid_id) {
              var hid = document.getElementById(hid_id);    
            //valor.value = valor.value.substr((code  + '_VD:').length, valor.value.length);
              hid.value = valor.value.substr((code  + '_VD:').length, valor.value.length);        
            }
    
            </script>";

  $string .= $javascript;
  
  $key_values = explode( ", ", $key_value);
  
  foreach ($select_array as $i => $value) {
    if ($select_array[$i]['status'] && !in_array($select_array[$i]['code'], $googlepayment->shipping_support)) {
      $name = (($key) ? 'configuration[' . $key . '][]' : 'configuration_value');
      $string .= "<br><b>" . $select_array[$i]['title'] . "</b>"."\n";
      if (is_array($googlepayment->mc_shipping_methods[$select_array[$i]['code']])) {
        foreach ($googlepayment->mc_shipping_methods[$select_array[$i]['code']] as $type => $methods) {
          if (is_array($methods) && !empty($methods)) {
            $string .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>'. $type .'</b>';            
            foreach($methods as $method => $method_name) {
              $string .= '<br>';
              
              // default value 
              $value = gc_compare($select_array[$i]['code'] . $method. $type , $key_values, 1);
            $string .= '<input size="5"  onBlur="VD_blur(this, \'' . $select_array[$i]['code']. $method . $type . '\', \'hid_' . $select_array[$i]['code'] . $method . $type . '\' );" onFocus="VD_focus(this, \'' . $select_array[$i]['code'] . $method . $type . '\' , \'hid_' . $select_array[$i]['code'] . $method . $type .'\');" type="text" name="no_use' . $method . '" value="' . $value . '"';
              $string .= '>';
            $string .= '<input size="10" id="hid_' . $select_array[$i]['code'] . $method . $type . '" type="hidden" name="' . $name . '" value="' . $select_array[$i]['code'] . $method . $type . '_VD:' . $value . '"';      
                $string .= '>'."\n";
                $string .= $method_name;
            }
          }
        }
      } else {
        $string .= $select_array[$i]['code'] .GOOGLECHECKOUT_MERCHANT_CALCULATION_NOT_CONFIGURED;
      }
    }
  }
  return $string;
}

?>
