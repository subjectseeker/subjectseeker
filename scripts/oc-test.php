#!/usr/local/bin/php

<?php

include "oc-globals.php";
include "oc-util.php";
require_once(dirname(__FILE__).'/../wp-includes/class-simplepie.php');

$db = ocDbConnect();

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
$feed = new SimplePie("http://feeds.feedburner.com/CieciaAoNatural");

print "Language: " . $feed->get_language() . "\n";

foreach ($feed->get_items() as $item) {
  print "Item title: " . $item->get_title() . "\n";
  print "Item date: " . dateStringToSql($item->get_local_date()) . "\n";
#  addSimplePieItem ($item, "en", 782, $db);
}

ocDbClose($db);

?>
