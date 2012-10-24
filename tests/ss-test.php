#!/usr/local/bin/php

<?php

include "ss-globals.php";
include "ss-util.php";
require_once(dirname(__FILE__).'/../wp-includes/simplepie.php');

$db = ssDbConnect();

/*
// Language testing
print "es -> " . languageToId("es", $db) . "\n";
print "es-419 -> " . languageToId("es-419", $db) . "\n";
print "es_419 -> " . languageToId("es-419", $db) . "\n";
print "Es -> " . languageToId("Es", $db) . "\n";
print "en -> " . languageToId("en", $db) . "\n";
print "en-gb -> " . languageToId("en-gb", $db) . "\n";
print "en_gb -> " . languageToId("en-gb", $db) . "\n";

*/

// Date testing
// print "Tue, 14 Dec 2010 13:39:28 +0000 ->" . dateStringToSql("Tue, 14 Dec 2010 13:39:28 +0000") . "\n";

/*
$feed = new SimplePie("http://www.google.com");
print "Google: " . $feed->get_type() . "\n";
$feed = new SimplePie("http://blog.coturnix.org/feed/");
print "coturnix: " . $feed->get_type() . "\n";
$feed = new SimplePie("http://www.imachordata.com/?feed=rss2");
foreach ($feed->get_items() as $item) {
  print "Post from I'm a Chordata: " . $item->get_permalink() . "\n";
}
print "Error from IAC: " . $feed->error() . "\n";
*/

/*
$feed = new SimplePie("http://arthropoda.southernfriedscience.com/?feed=rss2");
foreach ($feed->get_items() as $item) {
  print "Item title: " . $item->get_title() . "\n";
  addSimplePieItem ($item, "en", 6, $db);
}
*/

/*
$blogId = 2;
//mysql_query("SET CHARACTER SET 'utf8'", $db);
//mysql_query("set @@local.character_set_results = 'utf8'", $db);
//mysql_query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'", $db);
mysql_query("SET NAMES 'utf8'", $db);
$charset = mysql_client_encoding();
print "CHARSET: $charset\n";
$success = mysql_set_charset ("utf8", $db);
print "STATUS: $success\n";
print "ERROR: " . mysql_error() . "\n";
$charset = mysql_client_encoding();
print "CHARSET2: $charset\n";
print "NAME: " . getBlogName($blogId, $db) . "\n";

print "STR: " . $_REQUEST["STR"] . "<br />";
addBlog($_REQUEST["STR"], "http://www.imachordata.com/", "http://www.imachordata.com/?feed=comments-rss2", "", "Biology", null, 1, $db);


$str = "I'm a chordata!";
$esc = mysql_real_escape_string($str);
print "$str -> $esc<br />\n";
$esc = stripslashes($esc);
print "$str -> $esc<br />\n";
*/

#$feed = new SimplePie("http://neurosphere.wordpress.com/?feed=rss2");
#$feed = new SimplePie("http://feeds.feedburner.com/CieciaAoNatural");

#print "Language: " . $feed->get_language() . "\n";

#foreach ($feed->get_items() as $item) {
##  print "Item title: " . $item->get_title() . "\n";
#  print "Item date: " . dateStringToSql($item->get_local_date()) . "\n";
#  addSimplePieItem ($item, "en", 782, $db);
#}

# Multiple citations
$url = "http://charbonniers.org/2012/01/21/two-sides-of-synchrony/";
$sql = "SELECT BLOG_POST_ID, BLOG_POST_URI FROM BLOG_POST WHERE BLOG_POST_URI='$url'";
$results = mysql_query($sql, $db);
$posts = array();
while ($row = mysql_fetch_array($results)) {
  $info["url"] = $row["BLOG_POST_URI"];
  $info["id"] = $row["BLOG_POST_ID"];
  array_push($posts, $info);
 }

foreach ($posts as $post) {
  if (! citedPost($post["id"], $db)) {
    $citations = checkCitations ($post["url"], $post["id"], $db);
    foreach ($citations as $citation) {
      print "* Storing citation for post id=" . $post["id"] . " url=" . $post["url"] . " citation=$citation\n";
      storeCitationTest ($citation, $post["id"], $db);
    }
  } else {
    print "Skipping already-parsed post " . $post["id"] . "\n";
  }
}

$sql .= " ORDER BY BLOG_POST_INGEST_DATE_TIME DESC LIMIT 400";
$results = mysql_query($sql, $db);

function citedPost ($postId, $db) {
  $sql = "SELECT * FROM BLOG_POST WHERE BLOG_POST_HAS_CITATION=1 AND BLOG_POST_ID=$postId";
  $results = mysql_query($sql, $db);
  return (mysql_num_rows($results) != 0);
}

function storeCitationTest ($citation, $postId, $db) {
	
	$citation = mysql_escape_string(utf8_encode($citation));
	
	// Post has citation
	$markCitation = "UPDATE BLOG_POST SET BLOG_POST_HAS_CITATION=1 WHERE BLOG_POST_ID=$postId";
	mysql_query($markCitation, $db);
	
	// Check that the citation isn't already stored
	$citationId = citationTextToCitationId ($citation, $db);
	print "ID: using old ID ($citationId) for $citation\n";

	if ($citationId == NULL) {
		// Insert Citation
		$insertCitation = "INSERT IGNORE INTO CITATION (CITATION_TEXT) VALUES ('$citation')";
		mysql_query($insertCitation, $db);
		if (mysql_error()) {
			die ("InsertCitation: " . mysql_error() . "\n");
		}
		// Get citation ID
		$citationId = mysql_insert_id();
	print "ID: creating new ID ($citationId) for $citation\n";
	}
	
	// Assign citation ID to post ID
	$citationToPost = "INSERT IGNORE INTO POST_CITATION (CITATION_ID, BLOG_POST_ID) VALUES ('$citationId', '$postId')";
	print "citation ID <-> post ID: $citationToPost\n";
	mysql_query($citationToPost, $db);
	if (mysql_error()) {
		die ("CitationToPost: " . mysql_error() . "\n");
	}
}




ssDbClose($db);

?>
