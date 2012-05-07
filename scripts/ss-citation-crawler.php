#!/usr/local/bin/php
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link type="text/css" rel="stylesheet" href="">
<title>Citation Crawler</title>
</head>
<?php

include_once "ss-globals.php";
include_once "ss-util.php";

// Input: DB Handle
// Output: array of posts urls to be scanned for citations.
function getMarkedBlogs ($db) {
	$sql = "SELECT BLOG_ID FROM SCAN_POST WHERE MARKER_TYPE_ID = 1";
	$results = mysql_query($sql, $db);

	while ($row = mysql_fetch_array($results)) {
		$blogIds[] = $row["BLOG_ID"];
	}
	
	return $blogIds;
}

// Input: Blog ID, DB Handle
// Output: array of posts urls to be scanned for citations.
function getMarkedPosts ($blogId, $db) {
	$sql = "SELECT BLOG_POST_ID, BLOG_POST_URI FROM BLOG_POST WHERE BLOG_ID = $blogId ORDER BY BLOG_POST_DATE_TIME LIMIT 5";
	$results = mysql_query($sql, $db);
	
	$posts = array();
	while ($row = mysql_fetch_array($results)) {
		$post["id"] = $row["BLOG_POST_ID"];
		$post["blogId"] = $blogId;
		$post["url"] = $row["BLOG_POST_URI"];
		array_push($posts, $post);
	}
	
	return $posts;
}

// Input: DB Handle
// Action: Remove markers that are older than 5 days
function removeExpiredMarks ($db) {
	$sql = "DELETE FROM SCAN_POST WHERE MARKER_DATE_TIME < DATE_SUB(NOW(),INTERVAL 5 day)";
	$results = mysql_query($sql, $db);
}

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

// Extract the title from each feed in the $links list of feeds.
foreach ($links as $link) {
	$feed = new SimplePie();
	$feed->set_feed_url($link);
	$feed->enable_cache(false);
	$feed->set_output_encoding('UTF-8');
	$success = $feed->init();
	$feed->handle_content_type();
	
	if ($success) {
                // extract the titles of each post in the RB feed
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

// Compare the posts' titles with our database
$sql = "SELECT BLOG_POST_ID, BLOG_ID, BLOG_POST_URI FROM BLOG_POST WHERE BLOG_POST_TITLE='".mysql_real_escape_string($firstTitle)."'";
foreach ($titles as $title) {
	$sql .= " OR BLOG_POST_TITLE = '".mysql_real_escape_string($title)."'";
}
$sql .= " ORDER BY BLOG_POST_INGEST_DATE_TIME DESC LIMIT 400";
$results = mysql_query($sql, $db);

$posts = array();
while ($row = mysql_fetch_array($results)) {
	$info["url"] = $row["BLOG_POST_URI"];
	$info["id"] = $row["BLOG_POST_ID"];
	$info["blogId"] = $row["BLOG_ID"];
	array_push($posts, $info);
}

foreach ($links as $link) {
	$ch = curl_init(); // initialize curl handle
	curl_setopt($ch, CURLOPT_URL, $link); // set url
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return into a variable
	curl_setopt($ch, CURLOPT_HEADER, 0); // do not include the header
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
	$html = curl_exec($ch); // Execute curl
	curl_close($ch); // Close the connection
	
	$doc = new DOMDocument();
	@$doc->loadHTML($html);
	$xml = simplexml_import_dom($doc);
	
	// Extract the URL of each post
	$urlData = $xml->xpath('//link/@href');
	foreach ($urlData as $link) {
		$urls[] = (string)$link->href;
	}
}

$db = ssDbConnect();

$firstUrl = array_shift($urls);

// For all of the URLs we just extracted from the feed,
// find posts in our DB which have the same URL
$sql = "SELECT BLOG_POST_ID, BLOG_ID, BLOG_POST_URI FROM BLOG_POST WHERE BLOG_POST_URI='$firstUrl'";
foreach ($urls as $value) {
	$sql .= " OR BLOG_POST_URI = '$value'";
}
$sql .= " ORDER BY BLOG_POST_INGEST_DATE_TIME DESC LIMIT 400";
$results = mysql_query($sql, $db);

while ($row = mysql_fetch_array($results)) {
	$info["url"] = $row["BLOG_POST_URI"];
	$info["id"] = $row["BLOG_POST_ID"];
	$info["blogId"] = $row["BLOG_ID"];
	array_push($posts, $info);
}

// Get blogs from users that have generated a citation recently
$blogIds = getMarkedBlogs ($db);
foreach ($blogIds as $blogId) {
	$markedPosts = getMarkedPosts ($blogId, $db);
	foreach ($markedPosts as $markedPost) {
		array_push($posts, $markedPost);
	}
}
$blogIds = NULL;
$markedPosts = NULL;
$markedPost = NULL;

// Our $posts list now contains posts which have some common data
// with posts in the feed we're crawling.
// Scan each of these posts for citations.
// We may have retrieved some posts incorrectly (by title)
// but it is OK to scan posts that don't have citations, no harm is done.
foreach ($posts as $post) {
  if (! citedPost($post["id"], $db)) {
		$citations = checkCitations ($post["url"], $post["id"], $db);
		if (is_array($citations)) {
			insertCitationMarker ($post["blogId"], $db);
			foreach ($citations as $citation) {
				$articleData = parseCitation($citation);
				$generatedCitation = storeCitation ($articleData, $post["id"], $db);
				print "Storing citation for post id=" . $post["id"] . " url=" . $post["url"] . " citation=$generatedCitation\n";
			}
		}
  } else {
    print "Skipping already cited post " . $post["id"] . "\n";
  }
}

print "Done!";

removeExpiredMarks ($db);

ssDbClose($db);

?>