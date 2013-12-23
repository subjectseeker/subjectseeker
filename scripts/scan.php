<?php

// Put us in Eastern Time; eventually, this should be set by a
// parameter of some sort.
date_default_timezone_set( "America/New_York" );
header('Access-Control-Allow-Origin: *');

header('Content-Type: text/xml; charset=utf-8');

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/util.php");

$db = ssDbConnect();

if (isset($_REQUEST["query"]) == false && isset($_REQUEST["type"]) == false) {
	return NULL;
}

$type = $_REQUEST["type"];
$query = $_REQUEST["query"];

if ($type == "citation") {
	$results = titleToCitations($query);
	print "<subjectseeker>";
	if (is_array($results) == FALSE) {
	 print "<error>An error has occurred, please try again.</error>";
	 
	} else {
		print "
	<citations>";
		if ($results != NULL) {
			foreach ($results as $result) {
				print "
			<citation>".htmlspecialchars($result)."</citation>";
			}
		}
		print "	</citations>";
	}
	print "</subjectseeker>"
} else if ($type == "blog") {
	$parsedUrl = parse_url($query);
	$baseUrl = $parsedUrl["scheme"]."://".$parsedUrl["host"];

	$api = new API;
	$api->searchDb("filter0=url&value0=$baseUrl", FALSE, "blog");
	foreach ($api->sites as $site) {
		scanSite($site, $db);
		print "Scanned: ".$site["siteName"]."<br />";
	}
}


function scanSite($site, $db) {
	$siteId = $site["siteId"];
	$result = crawlBlogs($site, $db);

	$api = new API;
	$api->searchDb("type=post&filter0=blog&modifier0=identifier&value0=$siteId&n=10", FALSE, "post");
	foreach ($api->posts as $post) {
		$postId = $post["postId"];
		$postUri = $post["postUrl"];
		$postTitle = $post["postTitle"];
		$citations = checkCitations($postUri, $postId, $db);
		if (!empty($citations)) {
			foreach ($citations as $citation) {
				$articleData = parseCitation($citation);
				if (!empty($articleData)) {
					$generatedCitation = storeCitation ($articleData, $postId, $db);
				}
			}
		}
	}
}
?>