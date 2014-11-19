<?php
namespace WarhornGamedayTools;

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

   public function extractAndSetPFSNumber($person) {
      if (is_array($person['organized_play_memberships'])
          && isset($person['organized_play_memberships']['network'])
          && "Pathfinder Society" == $person['organized_play_memberships']['network']) {
         $this->_PFSNumber = $person['organized_play_memberships']['member_number'];
      } else {
         $this->_PFSNumber = "";
      }
   }//END public function extractPFSNumber($person)

}//END class Person
