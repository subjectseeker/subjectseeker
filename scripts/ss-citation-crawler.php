#!/usr/local/bin/php
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link type="text/css" rel="stylesheet" href="">
<title>Citation Crawler</title>
</head>
<?php

include_once "ss-globals.php";
include_once "ss-util.php";

// List of feeds to find common posts.
$links = array(
'http://www.researchblogging.org/feeds/alltopics/english.xml',
'http://www.researchblogging.org/feeds/alltopics/german.xml',
'http://www.researchblogging.org/feeds/alltopics/chinese.xml',
'http://www.researchblogging.org/feeds/alltopics/italian.xml',
'http://www.researchblogging.org/feeds/alltopics/polish.xml',
'http://www.researchblogging.org/feeds/alltopics/portuguese.xml',
'http://www.researchblogging.org/feeds/alltopics/spanish.xml'
);

// Extract the title from each feed.
foreach ($links as $link) {
	$feed = new SimplePie();
	$feed->set_feed_url($link);
	$feed->enable_cache(false);
	$feed->set_output_encoding('UTF-8');
	$success = $feed->init();
	$feed->handle_content_type();
	
	if ($success) {
		foreach ($feed->get_items() as $obj) {
			$data = $obj->get_item_tags('http://www.w3.org/2005/Atom', 'title');
			foreach ($data as $item) {
				$titles[] = $item["data"];
			}
		}
	}
	unset($feed);
}

// Connect to the database.
$db = ssDbConnect();

$firstTitle = array_shift($titles);

// Compare the feeds' titles with our database
$sql = "SELECT BLOG_POST_ID, BLOG_POST_URI FROM BLOG_POST WHERE BLOG_POST_TITLE='".mysql_real_escape_string($firstTitle)."'";
foreach ($titles as $title) {
	$sql .= " OR BLOG_POST_TITLE = '".mysql_real_escape_string($title)."'";
}
$sql .= " ORDER BY BLOG_POST_INGEST_DATE_TIME DESC LIMIT 400";
$results = mysql_query($sql, $db);

$posts = array();
while ($row = mysql_fetch_array($results)) {
	$info["url"] = $row["BLOG_POST_URI"];
	$info["id"] = $row["BLOG_POST_ID"];
	array_push($posts, $info);
}

// Scan common posts for citations
foreach ($posts as $post) {
  if (! citedPost($post["id"], $db)) {
	$citations = checkCitations ($post["url"], $post["id"], $db);
	foreach ($citations as $citation) {
   	print "Storing citation for post id=" . $post["id"] . " url=" . $post["url"] . " citation=$citation\n";
		storeCitation ($citation, $post["id"], $db);
	}
  } else {
    print "Skipping already cited post " . $post["id"] . "\n";
  }
}


print "Done!";

ssDbClose($db);

?>