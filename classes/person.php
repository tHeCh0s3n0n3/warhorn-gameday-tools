<?php
namespace WarhornGamedayTools;

require_once("MyObject.php");

class Person extends MyObject {

   protected $_PersonID;
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
      if (is_array($person['organized_play_memberships'])) {
         foreach($person['organized_play_memberships'] as $membership) {
            if (isset($membership['network'])
                && "Pathfinder Society" == $membership['network']) {
               $this->_PFSNumber = $membership['member_number'];
            }
         }
      } else {
         $this->_PFSNumber = "";
      }//end else
   }//END public function extractPFSNumber($person)

}//END class Person
