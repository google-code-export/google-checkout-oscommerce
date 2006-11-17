<?php
/*
  Copyright (C) 2006 Google Inc.

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

/* GOOGLE CHECKOUT
 * Class used to parse XML Data, uses SAX parser functions
 */
 
 
 //$xml->child[0]->value;
 /*
class xmlNode {
	var $value; // string
	var $children; // array number
 	var $attributes; // array hash
 	
}*/

class XmlParser {
  var $params= array();
  var $level = array();
	
  function XmlParser($input) {
    $xmlp = xml_parser_create();
    xml_parse_into_struct($xmlp, $input, $vals, $index);
    xml_parser_free($xmlp);
    //print_r($vals);
    //print_r($index);
    
    $this->updateMembers($vals, $index);
  }
	
  // Converts the data returned into PHP objects and stores the result in params array  
  function updateMembers($vals, $index) {
    foreach ($vals as $xml_elem) {
      if ($xml_elem['type'] == 'open') {
        $this->level[$xml_elem['level']] = strtolower($xml_elem['tag']);
      }
      if ($xml_elem['type'] == 'complete') {
        $xml_elem['tag'] = strtolower($xml_elem['tag']);
        $start_level = 1;
        $php_stmt = '$this->params';
        while($start_level < $xml_elem['level']) {
          $php_stmt .= '[$this->level['.$start_level.']]';
          $start_level++;
        }
        if(isset($xml_elem['attributes']))
        	$php_stmt .= '[$xml_elem[\'tag\']][] = array(\'value\' => $xml_elem[\'value\'], \'atributes\' => $xml_elem[\'attributes\']);';
        else
        	$php_stmt .= '[$xml_elem[\'tag\']][] = array(\'value\' => $xml_elem[\'value\']);';
        eval($php_stmt);
      }	
    }
  }
	
  function getRoot() {
    return $this->level[1];	
  }
	
  function getData() {
    return $this->params;	
  }
}
// ** END GOOGLE CHECKOUT ** 
?>
