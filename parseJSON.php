<?php

   require_once("constants.php");
   require_once("retrieveData.php");
   require_once("database.php");

   $json = RetrieveEventJSONData();
   //PrintArray($json);

   $slots = $json['slots'];
   //PrintArray($slots);

   $allEvents = array();

   foreach ($slots as $slot) {
      if (!isset($slot['sessions'][0])) {
         continue;
      }//end if

      $eventData = array();

      $i = 0;
      foreach ($slot['sessions'] as $session) {
         if (!isset($session['scenario']['name'])) {
            continue;
         }//end if

         $eventData[$i] = array("scenario-name" => $session['scenario']['name']
                              , "scenario-min-level" => $session['scenario']['min_level']
                              , "scenario-max-level" => $session['scenario']['max_level']
                              , "table-count" => $session['table_count']
                              , "gms" => $session['gms']
                              , "players" => array()
                             );
         foreach ($session['players'] as $player) {
            $eventData[$i]['players'][] = array("name" => $player['name']
                                                , "email" => $player['email']
                                                , "signed_up_at" => $player['signed_up_at']
                                                , "character-class" => (isset($player['character']['classes'][0]) ? $player['character']['classes'][0] : "")
                                                , "character-role" => (isset($player['character']['combatrole']) ? $player['character']['combatrole'] : "")
                                               );
         }//end foreach

         $i++;
      }//end foreach

      $allEvents[$slot['name']] = $eventData;
   }//end foreach

   // PrintArray($allEvents);

   // Save the info to the DB
   $db->storeAllDataToDB($allEvents);
   echo "done\n";
