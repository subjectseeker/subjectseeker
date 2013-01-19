<?php

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/../scripts/util.php");

header("Content-type: text/html; charset=utf-8");
$db = ssDbConnect();

/* Transfer Post Topics */

$sql = "SELECT * FROM POST_TOPIC ORDER BY BLOG_POST_ID ASC";
$results = mysql_query($sql, $db);

while ($row = mysql_fetch_array($results)) {
	$postId = $row["BLOG_POST_ID"];
	$topicId = $row["TOPIC_ID"];
	$topicSourceId = $row["TOPIC_SOURCE"];
	
	$sql = "INSERT IGNORE INTO TAG (TOPIC_ID, OBJECT_ID, OBJECT_TYPE_ID, TOPIC_SOURCE_ID, CREATION_DATE_TIME) VALUES ('$topicId', '$postId', '1', '0', NOW())";
	mysql_query($sql, $db);
}

/* Transfer Blog Topics */

$sql = "SELECT * FROM PRIMARY_BLOG_TOPIC ORDER BY BLOG_ID ASC";
$results =	mysql_query($sql, $db);
while ($row = mysql_fetch_array($results)) {
	$siteId = $row["BLOG_ID"];
	$topicId = $row["TOPIC_ID"];
	
	$sql = "INSERT IGNORE INTO TAG (TOPIC_ID, OBJECT_ID, OBJECT_TYPE_ID, TOPIC_SOURCE_ID, CREATION_DATE_TIME) VALUES ('$topicId', '$siteId', '3', '1', NOW())";
	mysql_query($sql, $db);
}

?>
