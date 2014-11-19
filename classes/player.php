<?php
namespace WarhornGamedayTools;

require_once("person.php");

class Player extends Person {

   protected $_SessionID;
   protected $_SignedUpOn;
   protected $_CharacterRole;
   protected $_CharacterClass;

   function __construct() {
      parent::__construct();

      $this->_SessionID = (int) 0;
      $this->_SignedUpOn = (int) 0;
      $this->_CharacterRole = "";
      $this->_CharacterClass = "";
   }//END public function __construct()

}//END class Player
