<?php

/**
 * Copyright (C) 2006 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

 /* This class is used to create items to be added to the shopping cart
  * Invoke a separate instance of this class for each item to be 
  * added to the cart.  
  * Required fields are the item name, description, quantity and price
  * The private-data and tax-selector for each item can be set in the 
  * constructor call or using individual Set functions
  */
  class GoogleItem {
     
    var $item_name; 
    var $item_description;
    var $unit_price;
    var $quantity;
    var $merchant_private_item_data;
    var $merchant_item_id;
    var $tax_table_selector;

    function GoogleItem($name, $desc, $qty, $price) {
      $this->item_name = $name; 
      $this->item_description= $desc;
      $this->unit_price = $price;
      $this->quantity = $qty;
    }
    
    function SetMerchantPrivateItemData($private_data) {
      $this->merchant_private_item_data = $private_data;  
    }

    function SetMerchantItemId($item_id) {
      $this->merchant_item_id = $item_id;  
    }
    
    function SetTaxTableSelector($tax_selector) {
      $this->tax_table_selector = $tax_selector;  
    }
  }
?>
