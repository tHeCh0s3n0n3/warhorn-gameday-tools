<?php

class MySQLDB {

  /**
   * This array holds the known person IDs so we don't have to keep hitting the DB
   * It is in the format of "E-Mail address"=>"PersonID". To check, just use isset().
   */
  private $known_person_IDs = array();

  private $known_person_hit = 0;
  private $known_person_miss = 0;

  function MySQLDB() {}

  /**
   * connect - Creates the PHP Database Object PDO and
   * returns it.
   *
   * @return PDO A connection to the DB using PDO or FALSE on failure
   * @throws PDOException if the connection attempt fails
   */
  function connect(){
    try {
      $dbh = new PDO('mysql:host='.DB_HOSTNAME.';dbname='.DB_DATABASE.';charset=utf8'
                     , DB_USERNAME
                     , DB_PASSWORD
                     , array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    } catch (PDOException $e) {
      return null;
    }

    return $dbh;
  }//END function Connect()

  /**
   * disconnect - Closes the supplied PDO connection
   *
   * @param PDO connection we want to close
   */
  function disconnect(&$dbh){
    $dbh = null;
  }//END function disconnect($dbh)

  function storeAllDataToDB($events) {
    $dbh = $this->connect();

    $i = 0;

    // Prepare our SQL queries
    $q_event_select = "SELECT COUNT(ID) AS \"EventCount\"
                              , ID
                         FROM Events
                        WHERE EventName = :EventName";
                          // AND EventStartTimestamp = :EventStartTimestamp
                          // AND EventEndTimestamp = :EventEndTimestamp";
    $q_event_insert = "INSERT INTO Events (
                          EventName
                          , EventStartTimestamp
                          , EventEndTimestamp
                          , InsertedOn
                       ) VALUES (
                          :EventName
                          , :EventStartTimestamp
                          , :EventEndTimestamp
                          , CURRENT_TIMESTAMP()
                       )";

    $q_session_select = "SELECT COUNT(SessionID) AS \"SessionCount\"
                                , SessionID
                           FROM Sessions
                          WHERE EventID = :EventID
                            AND SessionNumber = :SessionNumber
                            AND ScenarioName  = :ScenarioName";
    $q_session_insert = "INSERT INTO Sessions (
                          EventID
                          , SessionNumber
                          , ScenarioName
                          , ScenarioMinLevel
                          , ScenarioMaxLevel
                          , TableCount
                          , InsertedOn
                        ) VALUES (
                          :EventID
                          , :SessionNumber
                          , :ScenarioName
                          , :ScenarioMinLevel
                          , :ScenarioMaxLevel
                          , :TableCount
                          , CURRENT_TIMESTAMP()
                        )";

    $q_gm_delete = "DELETE FROM EventGMs
                     WHERE SessionID = :SessionID";
    $q_gm_insert = "INSERT INTO EventGMs (
                      SessionID
                      , PersonID
                      , SignedUpOn
                      , InsertedOn
                    ) VALUES (
                      :SessionID
                      , :PersonID
                      , :SignedUpOn
                      , CURRENT_TIMESTAMP()
                    )";

    $g_player_delete = "DELETE FROM EventPlayers
                         WHERE SessionID = :SessionID";
    $g_player_insert = "INSERT INTO EventPlayers (
                          SessionID
                          , PersonID
                          , SignedUpOn
                          , CharacterClass
                          , CharacterRole
                          , InsertedOn
                        ) VALUES (
                          :SessionID
                          , :PersonID
                          , :SignedUpOn
                          , :CharacterClass
                          , :CharacterRole
                          , CURRENT_TIMESTAMP()
                        )";

    $q_person_select = "SELECT COUNT(PersonID) AS \"PersonCount\"
                               , PersonID
                          FROM PersonMaster
                         WHERE PersonEMail = :PersonEMail";
    $q_person_insert = "INSERT INTO PersonMaster (
                          PersonName
                          , PersonEMail
                          , PersonPFSNumber
                          , OptedOut
                        ) VALUES (
                          :PersonName
                          , :PersonEMail
                          , :PersonPFSNumber
                          , :OptedOut
                        )";

    // Prepare our PDO Queries
    $sth_event_select = $dbh->prepare($q_event_select);
    $sth_event_insert = $dbh->prepare($q_event_insert);

    $sth_session_select = $dbh->prepare($q_session_select);
    $sth_session_insert = $dbh->prepare($q_session_insert);

    $sth_gm_delete = $dbh->prepare($q_gm_delete);
    $sth_gm_insert = $dbh->prepare($q_gm_insert);

    $sth_player_delete = $dbh->prepare($g_player_delete);
    $sth_player_insert = $dbh->prepare($g_player_insert);

    $sth_person_select = $dbh->prepare($q_person_select);
    $sth_person_insert = $dbh->prepare($q_person_insert);

    // Insert everything into the DB
    foreach ($events as $key=>$event) {
      $sth_event_select->bindValue(":EventName", $key);

      $sth_event_select->execute();
      $db_event = $sth_event_select->fetch(PDO::FETCH_ASSOC);

      $eventID = 0;
      if (0 < $db_event['EventCount']) {
        // This event exists, no need to insert it
        $eventID = $db_event['ID'];
      } else {
        // This event doesn't exist, create it
        $sth_event_insert->bindValue(":EventName", $key);
        $sth_event_insert->bindValue(":EventStartTimestamp", "0000-00-00 00:00:00");
        $sth_event_insert->bindValue(":EventEndTimestamp", "0000-00-00 00:00:00");

        $sth_event_insert->execute();

        // Get the newly inserted ID
        $db_event = $sth_event_select->fetch(PDO::FETCH_ASSOC);
        $eventID = $db_event['ID'];
      }//end else

      foreach ($event as $sessionNumber=>$session) {

        $sth_session_select->bindValue(":EventID", $eventID);
        $sth_session_select->bindValue(":SessionNumber", $sessionNumber);
        $sth_session_select->bindValue(":ScenarioName", $session['scenario-name']);

        $sth_session_select->execute();

        $db_session = $sth_session_select->fetch(PDO::FETCH_ASSOC);

        if (0 < $db_session['SessionCount']) {
          // This session already exists, no need to insert it
          $sessionID = $db_session['SessionID'];
        } else {
          // This session needs to be inserted
          $sth_session_insert->bindValue(":EventID", $eventID);
          $sth_session_insert->bindValue(":SessionNumber", $sessionNumber);
          $sth_session_insert->bindValue(":ScenarioName", $session['scenario-name']);
          $sth_session_insert->bindValue(":ScenarioMinLevel", $session['scenario-min-level']);
          $sth_session_insert->bindValue(":ScenarioMaxLevel", $session['scenario-max-level']);
          $sth_session_insert->bindValue(":TableCount", $session['table-count']);

          $sth_session_insert->execute();

          $db_session = $sth_session_select->fetch(PDO::FETCH_ASSOC);
          $sessionID = $db_session['SessionID'];
        }

        // Clear all GMs for this session
        $sth_gm_delete->bindValue(":SessionID", $sessionID);
        $sth_gm_delete->execute();

        // Insert the GM details into the DB
        foreach ($session['gms'] as $gm) {

          // Fetch the person ID
          $personID = $this->ReturnPersonID($sth_person_select
                                            , $sth_person_insert
                                            , $gm['name']
                                            , $gm['email']
                                            , (isset($gm['pfs']) ? $gm['pfs'] : null));

          $sth_gm_insert->bindValue(":SessionID", $sessionID);
          $sth_gm_insert->bindValue(":PersonID", $personID);
          $sth_gm_insert->bindValue(":SignedUpOn", $gm['signed_up_at']);

          $sth_gm_insert->execute();
        }//end foreach($gm)


        // Clear all Players for this session
        $sth_player_delete->bindValue(":SessionID", $sessionID);
        $sth_player_delete->execute();

        // Insert the players for this session
        foreach ($session['players'] as $player) {
          $i++;
          $personID = $this->ReturnPersonID($sth_person_select
                                            , $sth_person_insert
                                            , $player['name']
                                            , $player['email']
                                            , (isset($player['pfs']) ? $player['pfs'] : null));
          $sth_player_insert->bindValue(":SessionID", $sessionID);
          $sth_player_insert->bindValue(":PersonID", $personID);
          $sth_player_insert->bindValue(":SignedUpOn", $player['signed_up_at']);
          $sth_player_insert->bindValue(":CharacterClass", $player['character-class']);
          $sth_player_insert->bindValue(":CharacterRole", $player['character-role']);

          $sth_player_insert->execute();
        }//end foreach($player)

      }//end foreach($session)
    }//end foreach($event)

    $this->disconnect($dbh);

    echo "Processed: {$i} players.\n";
    echo "Known person hit: " . $this->known_person_hit . "\n";
    echo "Known person miss: " . $this->known_person_miss . "\n";
  }//END function storeAllDataToDB($events)

  private function ReturnPersonID ($sth_select, $sth_insert, $personName, $personEMail, $personPFSNumber) {
    // First check if we already have the person ID for the supplied email
    if (isset($this->known_person_IDs[$personEMail])) {
      $this->known_person_hit++;
      return $this->known_person_IDs[$personEMail];
    }//end if

    $this->known_person_miss++;

    // We didn't already have it, check if ther person already exists int eh DB
    $sth_select->bindValue(":PersonEMail", $personEMail);
    $sth_select->execute();

    $db_person = $sth_select->fetch(PDO::FETCH_ASSOC);

    if (0 < $db_person['PersonCount']) {
      // The person exists, record the ID in our knownPersonIDs variable and return the ID

      $this->known_person_IDs[$personEMail] = $db_person['PersonID'];
      return $db_person['PersonID'];

    } else {
      // The person does not exist in the DB. Insert it.
      $sth_insert->bindValue(":PersonName", $personName);
      $sth_insert->bindValue(":PersonEMail", $personEMail);
      $sth_insert->bindValue(":PersonPFSNumber", $personPFSNumber);
      $sth_insert->bindValue(":OptedOut", 0);

      $sth_insert->execute();

      $sth_select->execute();
      $db_person = $sth_select->fetch(PDO::FETCH_ASSOC);

      $this->known_person_IDs[$personEMail] = $db_person['PersonID'];

      return $db_person['PersonID'];
    }//end else
  }//END private function ReturnPersonID($sth_select, $sth_insert, $personName, $personEMail, $personPFSNumber)

}//END class MySQLDB

$db = new MySQLDB();
