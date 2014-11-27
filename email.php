<?php
namespace WarhornGamedayTools;

require_once("classes/PHPMailerAutoload.php");
require_once("constants.php");
require_once("database.php");

class EMail {

   private $_php_mailer;

   function __construct() {
      // Configure PHPMailer
      $mailer = new \PHPMailer();

      if (EMAIL_USE_SMTP) {
         $mailer->isSMTP();
         $mailer->Host = EMAIL_HOSTNAME;
         $mailer->Port = EMAIL_PORT;

         // Authentication
         $mailer->SMTPAuth = EMAIL_USE_AUTHENTICATION;
         if ($mailer->SMTPAuth) {
            $mailer->Username = EMAIL_USERNAME;
            $mailer->Password = EMAIL_PASSWORD;
         }//end if

         // Encryption
         if (EMAIL_USE_ENCRYPTION) {
            $mailer->SMTPSecure = EMAIL_ENCRYPTION_TYPE;
         }//end if
      }//end if

      $mailer->From = EMAIL_FROM_ADDRESS;
      if ("" != EMAIL_FROM_NAME) {
         $mailer->FromName = EMAIL_FROM_NAME;
      }//end if

      $this->_php_mailer = $mailer;
   }//END function __construct()

   public function addToEMailAddress($address, $name = "") {
      if ("" != $name) {
         $this->_php_mailer->addAddress($address, $name);
      } else {
         $this->_php_mailer->addAddress($address);
      }
   }//END public function addToEMailAddress($address, $name = "")

   public function setSubject($subject) {
      $this->_php_mailer->Subject = $subject;
   }//END public function setSubect($subject)

   public function getBody() {
      return $this->_php_mailer->Body;
   }

   public function setBody($body, $isHTML = false, $altBody = "") {
      if ($isHTML) {
         $this->_php_mailer->isHTML($isHTML);
         $this->_php_mailer->Body = $body;
         $this->_php_mailer->AltBody = $altBody;
      } else {
         $this->_php_mailer->Body = $body;
      }//end else
   }//END public function setBody($body)

   public function send() {
      return $this->_php_mailer->send();
   }//END public function send()

   public function setDebugLevel($dbgLevel) {
      $this->_php_mailer->SMTPDebug = $dbgLevel;
   }

   public function prepareAndSetEMailMessageBody($event, $gms) {
      $gms_list = $this->prepareGMsList($gms);

      $player_tables = $this->preparePlayerTablesContent($event['SessionID'], $event['TableSize']);

      $email_body = "<html>
                       <head></head>
                       <body>
                         <h1 style=\"color: #753502;\">
                           Warhorn Gameday Information for <br />
                           {$event['EventName']}
                         </h1>
                         <p>Dear GM(s);</p>
                         <p>Here is the information for &quot;<strong>{$event['ScenarioName']}</strong>&quot;:</p>
                         <table>
                           <tr>
                              <td style=\"text-align: right;\"># of tables:</td>
                              <td>{$event['TableCount']}</td>
                           </tr>
                           <tr>
                              <td style=\"text-align: right; vertical-align: top;\">GM(s):</td>
                              <td>{$gms_list}</td>
                           </tr>
                         </table>
                         <h2>Confirmed Players ({$player_tables['confirmed_count']} / {$event['TableSize']}):</h2>
                         <table style=\"border: 1px solid #000; border-collapse: collapse;\">
                           {$player_tables['confirmed']}
                         </table>";
      if (0 < $player_tables['waitlisted_count']) {
         $email_body .= "<h2>Waitlisted Players ({$player_tables['waitlisted_count']}):</h2>
                         <table style=\"border: 1px solid #000; border-collapse: collapse;\">
                           {$player_tables['waitlisted']}
                         </table>";
      }//end if

      $email_body .= "</body>
                     </html>";

      $this->setBody($email_body, true);
   }//END public function prepareEMailMessageBody($event)

   private function prepareGMsList($gms) {
      $gm_list = "";

      foreach ($gms as $gm) {
         $gm_list .= "{$gm['PersonName']} - {$gm['PersonPFSNumber']}<br />";
      }

      return $gm_list;
   }//END private function prepareGMsList($gms)

   private function preparePlayerTablesContent($sessionID, $table_size) {
      global $db;
      // Retrieve the player data from teh DB
      $player_data = $db->getPlayers($sessionID);

      // Prepare the table header rows
      $player_table = "<tr>
                         <th style=\"padding: 5px;\">Player Name</th>
                         <th style=\"padding: 5px;\">PFS Number</th>
                         <th style=\"padding: 5px;\">Character Class</th>
                         <th style=\"padding: 5px;\">Character Role</th>
                       </tr>";

      $player_waitlist_table = "<tr>
                                  <th style=\"padding: 5px;\">Player Name</th>
                                  <th style=\"padding: 5px;\">PFS Number</th>
                                  <th style=\"padding: 5px;\">Character Class</th>
                                  <th style=\"padding: 5px;\">Character Role</th>
                                </tr>";

      // Prepare the table content rows
      $i = 0;
      $confirmed_players_count = 0;
      $waitlisted_players_count = 0;

      foreach($player_data as $player) {
         // For the alternating (zebra) row colors
         $row_color = (($i % 2 == 0) ? "#949c51" : "white");

         if ($i <= $table_size){
            // This is a confirmed player
            $confirmed_players_count++;

            $player_table .= $this->preparePlayerRow($player, $row_color);

         } else {
            // This is a waitlisted player
            $waitlisted_players_count++;

            $player_waitlist_table .= $this->preparePlayerRow($player, $row_color);
         }
         $i++;
      }//end foreach

      return array("confirmed" => $player_table
                   , "confirmed_count" => $confirmed_players_count
                   , "waitlisted" => $player_waitlist_table
                   , "waitlisted_count" => $waitlisted_players_count);
   }//END private function preparePlayerTables($sessionID)

   private function preparePlayerRow($player, $row_color) {
      $retval = "<tr style=\"background-color: {$row_color};\">
                   <td style=\"padding: 5px;\">{$player['PersonName']}</td>";

      if ("" != $player['PersonPFSNumber']) {
         $retval .= "<td style=\"padding: 5px;\">{$player['PersonPFSNumber']}</td>";
      } else {
               $retval .= "<td style=\"padding: 5px; text-align: center;\">-</td>";
      }

      if ("" != $player['CharacterClass']) {
         $retval .= "<td style=\"padding: 5px;\">{$player['CharacterClass']}</td>";
      } else {
         $retval .= "<td style=\"padding: 5px; text-align: center;\">-</td>";
      }

      if ("" != $player['CharacterRole']) {
         $retval .= "<td style=\"padding: 5px;\">{$player['CharacterRole']}</td>";
      } else {
         $retval .= "<td style=\"padding: 5px; text-align: center;\">-</td>";
      }

      $retval .= "</tr>";

      return $retval;
   }//END private function preparePlayerRow($player)
}//END class EMail
