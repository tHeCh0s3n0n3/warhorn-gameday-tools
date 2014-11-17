<?php
namespace WarhornGamedayTools;

require_once("constants.php");
require_once("retrieveData.php");
require_once("WarhornJSONParser.php");
require_once("database.php");


$json = RetrieveEventJSONData();

$events = $warhornJSONParser->parseJSON($json);

// Save the info to the DB
$db->storeAllDataToDB($events);
echo "done\n";
