<?php

require_once("person.php");

class GM extends Person {

   protected $_SessionID;
   protected $_SignedUpOn;
   protected $_NotificationSent;


   function __construct() {
      parent::__construct();

      $this->_SessionID = (int) 0;
      $this->_SignedUpOn = (int) 0;
      $this->_NotificationSent = (int) 0;
   }//END public function __construct()

}//END class Player
