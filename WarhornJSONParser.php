<?php
/**
 * WarhornJSONParser.php
 */
namespace WarhornGamedayTools;

class WarhornJSONParser {

  private $allEvents = array();

  public function parseWarhornJSON($json) {
    $slots = $json['slots'];

    $this->allEvents = array();

    foreach ($slots as $slot) {
      if (!isset($slot['sessions'][0])) {
        // This event has not slots defined, skip it
        continue;
      }//end if

      $eventData = array("starts-at" => $slot["starts_at"]
                         , "ends-at" => $slot["ends_at"]
                        );

      $i = 0;
      foreach ($slot['sessions'] as $session) {
        if (!isset($session['scenario']['name'])) {
          continue;
        }//end if

        $eventData[$i] = array("scenario-name" => $session['scenario']['name']
                               , "scenario-min-level" => $session['scenario']['min_level']
                               , "scenario-max-level" => $session['scenario']['max_level']
                               , "table-count" => $session['table_count']
                               , "gms" => array()
                               , "players" => array()
                              );

        foreach ($session['gms'] as $gm) {
          $eventData[$i]['gms'][] = array("name" => $gm['name']
                                          , "email" => $gm['email']
                                          , "signed_up_at" => $gm['signed_up_at']
                                          , "pfs_number" => extractPFSNumber($gm)
                                         );
        }//end foreach ($gm)

        foreach ($session['players'] as $player) {
          $eventData[$i]['players'][] = array("name" => $player['name']
                                              , "email" => $player['email']
                                              , "signed_up_at" => $player['signed_up_at']
                                              , "pfs_number" => extractPFSNumber($player)
                                              , "character-class" => (isset($player['character']['classes'][0]) ? $player['character']['classes'][0] : "")
                                              , "character-role" => (isset($player['character']['combatrole']) ? $player['character']['combatrole'] : "")
                                             );
        }//end foreach ($player)

        $i++;
      }//end foreach ($session)

      // Place our fully constructed event data object into the allEvents array.
      $this->allEvents[$slot['name']] = $eventData;
    }//end foreach ($slot)

    return $this->allEvents;
  }//END public function parseWarhornJSON($json)

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

  function extractPFSNumber($person) {
     if (is_array($person['organized_play_memberships'])
         && isset($person['organized_play_memberships']['network'])
         && "Pathfinder Society" == $person['organized_play_memberships']['network']) {
        return $person['organized_play_memberships']['member_number'];
     } else {
        return null;
     }
  }//END function extractPFSNumber($person)
}//END class WarhornJSONParser

$warhornJSONParser = new warhornJSONParser;
