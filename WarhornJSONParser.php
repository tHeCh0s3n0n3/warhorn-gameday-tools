<?php
/**
 * WarhornJSONParser.php
 */
namespace WarhornGamedayTools;

require_once("classes/event.php");
require_once("classes/session.php");
require_once("classes/gm.php");
require_once("classes/player.php");

class WarhornJSONParser {

  private $allEvents = array();

  public function parseJSON($json) {
    $slots = $json['slots'];

    $this->allEvents = array();

    foreach ($slots as $slot) {
      if (!isset($slot['sessions'][0])) {
        // This event has not slots defined, skip it
        continue;
      }//end if

      $eventData = new Event();
      $eventData->setEventName($slot['name']);
      $eventData->setEventStart(strtotime($slot["starts_at"]));
      $eventData->setEventEnd(strtotime($slot["ends_at"]));

      $i = 0;
      foreach ($slot['sessions'] as $session) {
        if (!isset($session['scenario']['name'])) {
          continue;
        }//end if

        $new_session = new Session();

        $new_session->setSessionNumber($i);
        $new_session->setScenarioName($session['scenario']['name']);
        $new_session->setScenarioMinLevel($session['scenario']['min_level']);
        $new_session->setScenarioMaxLevel($session['scenario']['max_level']);
        $new_session->setTableCount($session['table_count']);

        foreach ($session['gms'] as $gm) {
          $new_gm = new GM();

          $new_gm->setName($gm['name']);
          $new_gm->setEMail($gm['email']);
          $new_gm->setSignedUpOn($gm['signed_up_at']);
          $new_gm->extractAndSetPFSNumber($gm);

          $new_session->addGM($new_gm);
        }//end foreach ($gm)

        foreach ($session['players'] as $player) {

          $new_player = new Player();

          $new_player->setName($player['name']);
          $new_player->setEMail($player['email']);
          $new_player->setSignedUpOn($player['signed_up_at']);
          $new_player->extractAndSetPFSNumber($player);
          $new_player->setCharacterClass((isset($player['character']['classes'][0]) ? $player['character']['classes'][0] : ""));
          $new_player->setCharacterRole((isset($player['character']['combatrole']) ? $player['character']['combatrole'] : ""));

          $new_session->addPlayer($new_player);
        }//end foreach ($player)

        $eventData->addSession($new_session);

        $i++;
      }//end foreach ($session)

      // Place our fully constructed event data object into the allEvents array.
      $this->allEvents[$slot['name']] = $eventData;
    }//end foreach ($slot)

    return $this->allEvents;
  }//END public function parseJSON($json)

  public function getAllEvents() {
    if (count($this->allEvents) > 0) {
      return $this->allEvents;
    } else {
      return false;
    }
  }//END public function GetAllEvents()

  public function isEventsParsed() {
    if (count($this->allEvents) > 0) {
      return true;
    } else {
      return false;
    }
  }//END public function isEventsParsed()


}//END class WarhornJSONParser

$warhornJSONParser = new warhornJSONParser;
