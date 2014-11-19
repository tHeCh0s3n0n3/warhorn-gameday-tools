<?php

require_once("MyObject.php");

class Event extends MyObject {

   protected $_EventID;
   protected $_EventName;
   protected $_EventStart;
   protected $_EventEnd;
   protected $_Sessions;

   function __constructor() {

      $this->_EventID = (int) 0;
      $this->_EventName = "";
      $this->_EventStart = (int) 0;
      $this->_EventEnd = (int) 0;
      $this->_Sessions = array();

   }//END function __constructor()

   public function addSession($session) {
      $this->Sessions[] = $session;
   }//END public function addSession($session)
}
