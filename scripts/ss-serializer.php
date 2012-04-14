<?php

include_once "ss-globals.php";
include_once "ss-util.php";

//$cache = new cache();

// Set the response header to indicate that this is Atom XML.
header( "Content-Type: application/atom+xml; charset=utf-8" );

// Put us in Eastern Time; eventually, this should be set by a
// parameter of some sort.
date_default_timezone_set( "America/New_York" );

$db = ssDbConnect();

$queryList = httpParamsToSearchQuery();
$settings = httpParamsToExtraQuery();
$settings["type"] = "post";
// TODO: parse an XML input file as well
// $params = parseSearchParams(file_get_contents('php://input'));

echo dbPublicSearch($queryList, $settings, $db);

ssDbClose($db);
?>
