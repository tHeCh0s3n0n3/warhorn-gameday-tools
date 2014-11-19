<?php

require_once("MyObject.php");

class Person extends MyObject {

   protected $_ID;
   protected $_Name;
   protected $_EMail;
   protected $_PFSNumber;
   protected $_OptedOut;

   function __construct() {
      $this->_ID = (int) 0;
      $this->_Name = "";
      $this->_EMail = "";
      $this->_PFSNumber = "";
      $this->_OptedOut = false;

      return $this;
   }//END public Person()

}//END class Person
