#!/usr/local/bin/php

<?php

include_once "ss-globals.php";
include_once "ss-util.php";

$feeds = array(
'http://www.researchblogging.org/feeds/alltopics/english.xml',
'http://www.researchblogging.org/feeds/alltopics/german.xml',
'http://www.researchblogging.org/feeds/alltopics/chinese.xml',
'http://www.researchblogging.org/feeds/alltopics/italian.xml',
'http://www.researchblogging.org/feeds/alltopics/polish.xml',
'http://www.researchblogging.org/feeds/alltopics/portuguese.xml',
'http://www.researchblogging.org/feeds/alltopics/spanish.xml'
);

foreach ($feeds as $feed) {
	$ch = curl_init(); // initialize curl handle
	curl_setopt($ch, CURLOPT_URL, $feed); // set url
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //return into a variable
	curl_setopt($ch, CURLOPT_HEADER, 0); // do not include the header
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
	curl_setopt($ch, CURLOPT_TIMEOUT, 8); // times out after 8s
	$html = curl_exec($ch); // Execute curl
	curl_close($ch); // Close the connection
	
	$doc = new DOMDocument();
	@$doc->loadHTML($html);
	$xml = simplexml_import_dom($doc);
	
	$urlData = $xml->xpath('//link/@href');
	foreach ($urlData as $link) {
		$urls[] = (string)$link->href;
	}
}

$db = ssDbConnect();

$firstUrl = array_shift($urls);

$sql = "SELECT BLOG_POST_ID, BLOG_POST_URI FROM BLOG_POST WHERE BLOG_POST_URI='$firstUrl'";
foreach ($urls as $value) {
	$sql .= " OR BLOG_POST_URI = '$value'";
}
$sql .= " ORDER BY BLOG_POST_INGEST_DATE_TIME DESC LIMIT 400";
$results = mysql_query($sql, $db);

$posts = array();
while ($row = mysql_fetch_array($results)) {
	$info["url"] = $row["BLOG_POST_URI"];
	$info["id"] = $row["BLOG_POST_ID"];
	array_push($posts, $info);
}

foreach ($posts as $post) {
	$citations = checkCitations ($post["url"], $post["id"], $db);
	foreach ($citations as $citation) {
		storeCitation ($citation, $post["id"], $db);
	}
}

print "Done!";

ssDbClose($db);

?>