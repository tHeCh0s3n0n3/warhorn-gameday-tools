<?php
namespace WarhornGamedayTools;

abstract class MyObject {

   public function __call($method, $args) {
      $key = '_' . substr($method, 3);
      $value = isset($args[0]) ? $args[0] : null;
      switch (substr($method, 0, 3)) {
         case "get":
            if (property_exists($this, $key)) {
               return $this->$key;
            }//end if
            break;

         case "set":
            if (property_exists($this, $key)) {
               $this->$key = $value;
               return $this;
            }//end if
            break;

         case "has":
            return property_exists($this, $key);
            break;
      }//end switch

      throw new \Exception("Method \"{$method}\" does not exist and was not trapped in __call()");
   }//END public function __call($method, $args)
}//END abstract class MyObject
