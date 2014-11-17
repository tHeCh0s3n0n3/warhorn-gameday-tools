<?php
namespace WarhornGamedayTools;

require_once("constants.php");
require_once("retrieveData.php");
require_once("WarhornJSONParser.php");
require_once("database.php");


$json = RetrieveEventJSONData();
// PrintArray($allEvents);

// Save the info to the DB
$db->storeAllDataToDB($allEvents);
echo "done\n";
