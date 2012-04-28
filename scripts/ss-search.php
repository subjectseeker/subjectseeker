<?php

// Put us in Eastern Time; eventually, this should be set by a
// parameter of some sort.
date_default_timezone_set( "America/New_York" );

include_once "ss-globals.php";
include_once "ss-util.php";

global $dbName;

$db = ssDbConnect();
$queryList = httpParamsToSearchQuery($parsedQuery);
$settings = httpParamsToExtraQuery($parsedQuery);
// TODO: parse an XML input file as well
// $params = parseSearchParams(file_get_contents('php://input'));

if (strstr($_SERVER["REQUEST_URI"], "feed")) {
	header( "Content-Type: application/atom+xml; charset=utf-8" );
	$settings["type"] = "post";
	$settings["citation-in-summary"] = "true";
}
else {
	header('Content-Type: text/xml; charset=utf-8');
}

echo dbPublicSearch($queryList, $settings, $db);

ssDbClose($db);

?>