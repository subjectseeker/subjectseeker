<?php

header('Content-Type: text/xml; charset=utf-8');

include_once "ss-globals.php";
include_once "ss-util.php";

global $dbName;

$db = ssDbConnect();
$queryList = httpParamsToSearchQuery($parsedQuery);
$settings = httpParamsToExtraQuery($parsedQuery);
// TODO: parse an XML input file as well
// $params = parseSearchParams(file_get_contents('php://input'));

echo dbPublicSearch($queryList, $settings, $db);

ssDbClose($db);
?>