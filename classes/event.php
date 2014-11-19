<?php
namespace WarhornGamedayTools;

require_once("MyObject.php");

class Event extends MyObject {

   protected $_EventID;
   protected $_EventName;
   protected $_EventStart;
   protected $_EventEnd;
   protected $_Sessions;

   function __construct() {

      $this->_EventID = (int) 0;
      $this->_EventName = "";
      $this->_EventStart = (int) 0;
      $this->_EventEnd = (int) 0;
      $this->_Sessions = array();

   }//END function __construct()

   public function addSession($session) {
      $this->_Sessions[] = $session;
   }//END public function addSession($session)
}
