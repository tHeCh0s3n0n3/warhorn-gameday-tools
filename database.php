<?php
/**
 * database.php
 *
 * The Database class is meant to simplify the task of
 * inserting, changing and accessing information
 * to/from the DB.
 *
 * @category warhorn-gameday-tools
 * @package  warhorn-gameday-tools
 * @author Tarek Fadel <dev@tarekfadel.com>
 * @since 2014-10-30
 * @version  v0.01
 */
namespace WarhornGamedayTools;

use PDO;

/**
 * Contains the DB_* constants we use to connect to the DB
 */
require_once("constants.php");

/**
 * This takes care of ALL the database interactions. It is
 * instanticated from within this file. This class's main
 * purpose is to sperate all DB logic from everything else
 *
 * @category warhorn-gameday-tools
 * @package  warhorn-gameday-tools
 * @author Tarek Fadel <dev@tarekfadel.com>
 * @since 2014-10-30
 * @version v0.01
 */
class MySQLDB {

  /**
   * This array holds the known person IDs so we don't
   * have to keep hitting the DB. It is in the format
   * of "E-Mail address"=>"PersonID". To check, just
   * use isset().
   *
   * @var array
   */
  private $known_person_IDs = array();

  /**
   * Used for keeping track of information used for debugging.
   *
   * @var integer
   */
  private $known_person_hit = 0;
  private $known_person_miss = 0;

  /**
   * MySQLDB default constructor.
   */
  function MySQLDB() {}

  /**
   * Creates the PHP Database Object PDO and
   * returns it.
   *
   * @return PDO A connection to the DB using PDO or FALSE on failure
   *
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
   * Closes the supplied PDO connection
   *
   * @param PDO connection we want to close
   */
  function disconnect(&$dbh){
    $dbh = null;
  }//END function disconnect($dbh)

  public function getEventsStartingToday() {
    $q = "SELECT E.ID
                 , E.EventName
                 , S.SessionID
                 , S.ScenarioName
                 , S.ScenarioMinLevel
                 , S.ScenarioMaxLevel
                 , S.TableCount
                 , S.TableSize
            FROM Sessions S
                     LEFT JOIN Events E
                         ON S.EventID = E.ID
           WHERE E.EventStartTimestamp
                 BETWEEN :StartTimestamp
                     AND :EndTimestamp";
    $dbh = $this->connect();

    $sth = $dbh->prepare($q);

    //$sth->bindValue(":StartTimestamp", date("c", mktime(0, 0, 0)));
    //$sth->bindValue(":EndTimestamp", date("c", mktime(23, 59, 59)));
    $sth->bindValue(":StartTimestamp", date("c", mktime(0, 0, 0, 11, 21, 2014)));
    $sth->bindValue(":EndTimestamp", date("c", mktime(23, 59, 59, 11, 21, 2014)));

    $sth->execute();

    $db_data = $sth->fetchAll(PDO::FETCH_ASSOC);

    $this->disconnect($dbh);

    return $db_data;
  }//END getEventsStartingToday()

  public function getGMs($sessionID) {
    $dbh = $this->connect();

    $q = "SELECT PM.PersonName
                 , PM.PersonPFSNumber
                 , PM.PersonEMail
            FROM EventGMs EG
                   LEFT JOIN PersonMaster PM
                     ON EG.PersonID = PM.PersonID
           WHERE EG.SessionID = :SessionID";

    $sth = $dbh->prepare($q);
    $sth->bindValue(":SessionID", $sessionID);

    $sth->execute();

    $retval = $sth->fetchAll(PDO::FETCH_ASSOC);

    $this->disconnect($dbh);

    return $retval;

  }//END public function getGMs($sessionID)

  public function getPlayers($sessionID) {
    $dbh = $this->connect();

    $q = "SELECT PM.PersonName
                 , PM.PersonPFSNumber
                 , EP.CharacterClass
                 , EP.CharacterRole
            FROM EventPlayers EP
                   LEFT JOIN PersonMaster PM
                     ON EP.PersonID = PM.PersonID
           WHERE EP.SessionID = :SessionID
           ORDER BY EP.SignedUpOn ASC";

    $sth = $dbh->prepare($q);
    $sth->bindValue(":SessionID", $sessionID);

    $sth->execute();

    $retval = $sth->fetchAll(PDO::FETCH_ASSOC);

    $this->disconnect($dbh);

    return $retval;
  }


  /**
   * Saves new data and removes data which is no longer valid from the DB.
   *
   * @param  array $events The entire event data previously prepared.
   *
   * @return null This function does not return anything.
   */
  function storeAllDataToDB(&$events) {
    // Initialize the DB connection
    $dbh = $this->connect();

    // For keeping track of some statistics used in debugging.
    $numberofGMsTotal = 0;
    $numberofGMsInserted = 0;
    $numberofPlayersTotal = 0;
    $numberOfPlayersInserted = 0;

    // Prepare our SQL queries
    /* EVENT */
    $q_event_select = "SELECT COUNT(ID) AS \"EventCount\"
                              , ID
                         FROM Events
                        WHERE EventName = :EventName
                          AND EventStartTimestamp = :EventStartTimestamp
                          AND EventEndTimestamp = :EventEndTimestamp";

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

    /* SESSION */
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
                          , TableSize
                          , InsertedOn
                        ) VALUES (
                          :EventID
                          , :SessionNumber
                          , :ScenarioName
                          , :ScenarioMinLevel
                          , :ScenarioMaxLevel
                          , :TableCount
                          , :TableSize
                          , CURRENT_TIMESTAMP()
                        )";

    /* GM */
    $q_gm_delete = "DELETE FROM EventGMs
                     WHERE SessionID = :SessionID
                       AND PersonID = :PersonID";

    $q_gm_insert = "INSERT INTO EventGMs (
                      SessionID
                      , PersonID
                      , SignedUpOn
                      , NotificationSent
                      , InsertedOn
                    ) VALUES (
                      :SessionID
                      , :PersonID
                      , :SignedUpOn
                      , :NotificationSent
                      , CURRENT_TIMESTAMP()
                    )";

    /* PLAYER */
    $g_player_delete = "DELETE FROM EventPlayers
                         WHERE SessionID = :SessionID
                           AND PersonID = :PersonID";

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

    /* PERSON */
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

    // Prepare the GM and Player data for comsumption later
    $existing_gm_data = $this->prepareExistingGMData($dbh);
    $existing_player_data = $this->prepareExistingPlayerData($dbh);

    /*** Event ***/

    // Insert everything into the DB
    foreach ($events as $key=>$event) {
      //@TODO UNCOMMENT THIS ONCE JSON DATA IS AVABILABLE
      // if ($event->getEventStart() < time()) {
      //   // This event is the past, we don't need to process it
      //   continue;
      // }//end if

      $sth_event_select->bindValue(":EventName", $key);
      $sth_event_select->bindValue(":EventStartTimestamp", date("c", $event->getEventStart()));
      $sth_event_select->bindValue(":EventEndTimestamp", date("c", $event->getEventEnd()));

      $sth_event_select->execute();
      $db_event = $sth_event_select->fetch(PDO::FETCH_ASSOC);

      if (0 < $db_event['EventCount']) {
        // This event exists, no need to insert it

        $event->setEventID($db_event['ID']);
      } else {
        // This event doesn't exist, create it

        $sth_event_insert->bindValue(":EventName", $key);
        $sth_event_insert->bindValue(":EventStartTimestamp", date("c", $event->getEventStart()));
        $sth_event_insert->bindValue(":EventEndTimestamp", date("c", $event->getEventEnd()));

        $sth_event_insert->execute();
        $this->errorHandler($sth_event_insert->errorInfo(), "Event insert");

        // Get the newly inserted ID
        $sth_event_select->execute();
        $this->errorHandler($sth_event_select->errorInfo(), "Event select after insert");

        // Extract the eventID
        $db_event = $sth_event_select->fetch(PDO::FETCH_ASSOC);
        $event->setEventID($db_event['ID']);
      }//end else

      /*** Session ***/
      foreach ($event->getSessions() as $sessionNumber=>$session) {

        // Check if we are processing the start-timestamp or end-timestamp array element
        // if (!is_numeric($sessionNumber)) {
        //   // There is nothing to process here, skip this entry

        //   continue;
        // }//end if

        $sth_session_select->bindValue(":EventID", $event->getEventID());
        $sth_session_select->bindValue(":SessionNumber", $session->getSessionNumber());
        $sth_session_select->bindValue(":ScenarioName", $session->getScenarioName());

        $sth_session_select->execute();
        $this->errorHandler($sth_session_select->errorInfo(), "Session select");

        $db_session = $sth_session_select->fetch(PDO::FETCH_ASSOC);

        if (0 < $db_session['SessionCount']) {
          // This session already exists, no need to insert it

          $session->setSessionID($db_session['SessionID']);
        } else {
          // This session needs to be inserted

          $sth_session_insert->bindValue(":EventID", $event->getEventID());
          $sth_session_insert->bindValue(":SessionNumber", $session->getSessionNumber());
          $sth_session_insert->bindValue(":ScenarioName", $session->getScenarioName());
          $sth_session_insert->bindValue(":ScenarioMinLevel", $session->getScenarioMinLevel());
          $sth_session_insert->bindValue(":ScenarioMaxLevel", $session->getScenarioMaxLevel());
          $sth_session_insert->bindValue(":TableCount", $session->getTableCount());
          $sth_session_insert->bindValue(":TableSize", $session->getTableSize());

          $sth_session_insert->execute();
          $this->errorHandler($sth_session_insert->errorInfo(), "Session insert");

          // Get the newly inserted ID
          $sth_session_select->execute();
          $this->errorHandler($sth_session_select->errorInfo(), "Session select after insert");

          // Extract the sessionID
          $db_session = $sth_session_select->fetch(PDO::FETCH_ASSOC);
          $session->setSessionID($db_session['SessionID']);
        }//end else

        /*** GMs ***/
        $existing_gm_ids = array();
        $parsed_gm_ids = array();

        // Get the person ID(s) of the GMs already in the DB
        if (is_array($existing_gm_data)
            && isset($existing_gm_data[$session->getSessionID()])) {
          $existing_gm_ids = array_keys($existing_gm_data[$session->getSessionID()]);
        }//end if

        // Insert the GM details into the DB
        foreach ($session->getGMs() as $gm) {
          $numberofGMsTotal++;

          // Fetch the person ID
          $gm->setPersonID($this->insertAndGetPersonID($sth_person_select
                                                       , $sth_person_insert
                                                       , $gm->getName()
                                                       , $gm->getEMail()
                                                       , $gm->getPFSNumber()));

          if (false === array_search($gm->getPersonID(), $existing_gm_ids)) {
            // This GM isn't already in the list, insert it
            $numberofGMsInserted++;

            $sth_gm_insert->bindValue(":SessionID", $session->getSessionID());
            $sth_gm_insert->bindValue(":PersonID", $gm->getPersonID());
            $sth_gm_insert->bindValue(":SignedUpOn", $gm->getSignedUpOn());
            $sth_gm_insert->bindValue(":NotificationSent", $gm->getNotificationSent());

            $sth_gm_insert->execute();
            $this->errorHandler($sth_gm_insert->errorInfo(), "GM insert");
          }//end if

          // Add this GM to the list of GMs (for use in determining which GMs
          // are no longer needed in the DB)
          $parsed_gm_ids[] = $gm->getPersonID();

        }//end foreach($gm)

        // Check if there are GMs to remove
        $gms_to_remove = array_diff($existing_gm_ids, $parsed_gm_ids);

        if (count($gms_to_remove) > 0) {
          // We have GMs to remove
          foreach($gms_to_remove as $gm_to_remove) {
            $sth_gm_delete->bindValue(":SessionID", $session->getSessionID());
            $sth_gm_delete->bindValue(":PersonID", $gm_to_remove);

            $sth_gm_delete->execute();
            $this->errorHandler($sth_gm_delete->errorInfo(), "GM delete");
          }//end foreach
        }//end if

        /*** Players ***/
        $existing_player_ids = array();
        $parsed_player_ids = array();

        // Get the person ID(s) of the players already in the DB
        if (is_array($existing_player_data)
            && isset($existing_player_data[$session->getSessionID()])) {
          $existing_player_ids = array_keys($existing_player_data[$session->getSessionID()]);
        }

        if (!is_array($session->getPlayers())) {
          echo "Event: {$event->getEventName()} | Session: {$session->getSessionNumber()}\n";
          echo "val: \n"; var_dump($session->getPlayers()); echo "\n";
        }
        // Insert the players for this session
        foreach ($session->getPlayers() as $player) {
          $numberofPlayersTotal++;
          $player->setPersonID($this->insertAndGetPersonID($sth_person_select
                                                           , $sth_person_insert
                                                           , $player->getName()
                                                           , $player->getEMail()
                                                           , $player->getPFSNumber()));

          if (false === array_search($player->getPersonID(), $existing_player_ids)) {
            // This player isn't already in the list, insert it

            $numberOfPlayersInserted++;

            $sth_player_insert->bindValue(":SessionID", $session->getSessionID());
            $sth_player_insert->bindValue(":PersonID", $player->getPersonID());
            $sth_player_insert->bindValue(":SignedUpOn", $player->getSignedUpOn());
            $sth_player_insert->bindValue(":CharacterClass", $player->getCharacterClass());
            $sth_player_insert->bindValue(":CharacterRole", $player->getCharacterRole());

            $sth_player_insert->execute();
            $this->errorHandler($sth_player_insert->errorInfo(), "Player insert");
          }//end if

          // Add this player to the list of players (for use in determining which players
          // are no longer needed in the DB)
          $parsed_player_ids[] = $player->getPersonID();

        }//end foreach($player)

        // Check if there are players to remove
        $players_to_remove = array_diff($existing_player_ids, $parsed_player_ids);

        if (count($players_to_remove) > 0) {
          // We have players to remove

          foreach($players_to_remove as $player_to_remove) {
            $sth_player_delete->bindValue(":SessionID", $session->getSessionID());
            $sth_player_delete->bindValue(":PersonID", $player_to_remove);

            $sth_player_delete->execute();
            $this->errorHandler($sth_player_delete->errorInfo(), "Player delete");
          }//end foreach
        }//end if
      }//end foreach($session)
    }//end foreach($event)

    $this->disconnect($dbh);

    // Only ouput these statistics of debugging is enabled
    if (DEBUG) {
      echo "GMs: Total: {$numberofGMsTotal} | Newly Inserted: {$numberofGMsInserted}\n";
      echo "Players: Total: {$numberofPlayersTotal} | Newly Inserted: {$numberOfPlayersInserted}\n";
      echo "Known person hit: " . $this->known_person_hit . "\n";
      echo "Known person miss: " . $this->known_person_miss . "\n";
    }//end if
  }//END function storeAllDataToDB($events)

  /**
   * Takes care of inserting people records into the DB and returning the resulting ID. This function
   * will also use a list of known IDs in order to minimize expensive DB access.
   *
   * @param  PDOStatement $sth_select      The previously prepared PDOStatement for selecting a single person record from the DB.
   * @param  PDOStatement $sth_insert      The previously prepared PDOStatement for inserting a person record into the DB.
   * @param  string       $personName      Name of the person record being inserted.
   * @param  string       $personEMail     E-Mail address of the person record being inserted.
   * @param  string       $personPFSNumber PFS Number of the person record being inserted.
   *
   * @return int The database ID column for the inserted (or cached) person record.
   */
  private function insertAndGetPersonID ($sth_select, $sth_insert, $personName, $personEMail, $personPFSNumber) {
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
  }//END private function insertAndGetPersonID($sth_select, $sth_insert, $personName, $personEMail, $personPFSNumber)

  /**
   * Prepares the GM data from the DB and returns it in an associative array.
   *
   * @param  PDO   $dbh The PDO object on which we can call PDO::prepare()
   *
   * @return array An associative array which holds all the GM data from the DB.
   */
  private function prepareExistingGMData($dbh) {

    // @TODO add WHERE E.EventStartTimestamp >= CURRENT_TIMESTAMP()
    $q = "SELECT EG.ID
                 , EG.SessionID
                 , EG.PersonID
                 , EG.SignedUpOn
                 , PM.PersonEMail
            FROM EventGMs EG
                 LEFT JOIN Sessions S
                   LEFT JOIN Events E
                     ON S.EventID = E.ID
                   ON EG.SessionID = S.SessionID
                 INNER JOIN PersonMaster PM
                   ON EG.PersonID = PM.PersonID
           ORDER BY EG.SessionID ASC
                    , EG.ID";

      $sth = $dbh->prepare($q);

      $sth->execute();

      $result = $sth->fetchAll(PDO::FETCH_ASSOC);

      $retval = array();

      foreach($result as $gm) {
        $retval[$gm['SessionID']][$gm['PersonID']] = $gm;
      }

      return $retval;
    }//END private funciton prepareExistingGMData($dbh)

  /**
   * Prepares the Player data from the DB and returns it in an associative array.
   *
   * @param  PDO   $dbh The PDO object on which we can call PDO::prepare()
   *
   * @return array An associative array which holds all the player data from the DB.
   */
  private function prepareExistingPlayerData($dbh) {
    // @TODO add WHERE E.EventStartTimestamp >= CURRENT_TIMESTAMP()
    $q = "SELECT EP.ID
                 , EP.SessionID
                 , EP.PersonID
                 , EP.SignedUpOn
                 , PM.PersonEMail
                 , EP.CharacterClass
                 , EP.CharacterRole
            FROM EventPlayers EP
                 LEFT JOIN Sessions S
                   LEFT JOIN Events E
                     ON S.EventID = E.ID
                   ON EP.SessionID = S.SessionID
                 INNER JOIN PersonMaster PM
                   ON EP.PersonID = PM.PersonID
           ORDER BY EP.SessionID ASC
                    , EP.ID";

    $sth = $dbh->prepare($q);
    $sth->execute();
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);

    $retval = array();
    foreach($result as $player) {
      $retval[$player['SessionID']][$player['PersonID']] = $player;
    }

    return $retval;
  }//END private function prepareExistingPlayerData($dbh)

  /**
   * Takes core of displaying SQL errors (if there are any).
   *
   * @param  array  $errorInfo    Array from $sth->errorInfo().
   * @param  string $friendlyName The friendly name which will prefix the error message.
   *
   * @return null This function does not return anything.
   */
  private function errorHandler($errorInfo, $friendlyName) {

    if (DEBUG && "00000" != $errorInfo[0]) {
      // only output the error if debugging is enabled.

      echo "{$friendlyName} [{$errorInfo[0]}]: SQL Error ({$errorInfo[1]}): {$errorInfo[2]}\n";
    }//end if

  }//END private function errorHandler($errorInfo, $friendlyName)

}//END class MySQLDB

$db = new MySQLDB();
