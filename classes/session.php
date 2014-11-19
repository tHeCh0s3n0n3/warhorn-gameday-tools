<?php
namespace WarhornGamedayTools;

require_once("MyObject.php");

class Session extends MyObject {

   protected $_SessionID;
   protected $_EventID;
   protected $_SessionNumber;
   protected $_ScenarioName;
   protected $_ScenarioMinLevel;
   protected $_ScenarioMaxLevel;
   protected $_TableCount;
   protected $_GMs;
   protected $_Players;

   function __contruct() {

      $this->_SessionID = (int) 0;
      $this->_EventID = (int) 0;
      $this->_SessionNumber = (int) 0;
      $this->_ScenarioName = "";
      $this->_ScenarioMinLevel = (int) 0;
      $this->_ScenarioMaxLevel = (int) 0;
      $this->_TableCount = (int) 0;
      $this->_GMs = array();
      $this->_Players = array();

   }//END function __contruct()

   public function addGM($gm) {
      $this->_GMs[] = $gm;
   }//END public function addGM($gm)

   public function addPlayer($player) {
      $this->_Players[] = $player;
   }//END public function addPlayer($player)
}
