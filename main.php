<?php
namespace WarhornGamedayTools;

require_once("constants.php");
require_once("retrieveData.php");
require_once("WarhornJSONParser.php");
require_once("database.php");
require_once("email.php");



$json = RetrieveEventJSONData();

$events = $warhornJSONParser->parseJSON($json);

// Save the info to the DB
$db->storeAllDataToDB($events);

// Send the e-mail
$db_data = $db->getEventsStartingToday();
foreach ($db_data as $event) {
   // Retrieve the GM data since we'll need it here
   $gm_data = $db->getGMs($event['SessionID']);

   $mailer = new EMail();

   // $mailer->setDebugLevel(3);

   foreach ($gm_data as $gm) {
      $mailer->addToEMailAddress($gm['PersonEMail'], $gm['PersonName']);
   }//end foreach

   $mailer->setSubject("[WarHorn Gameday] Players for {$event['EventName']}: {$event['ScenarioName']}");
   $mailer->prepareAndSetEMailMessageBody($event, $gm_data);

   echo $mailer->getBody();

   if ($mailer->send()) {
      echo "EMail sent!\n";
   } else {
      echo "EMail ***NOT*** sent.\n";
   }
}//end foreach






echo "done\n";
