<?php

include_once (dirname(__FILE__)."/../config/globals.php");
include_once (dirname(__FILE__)."/util.php");

function displayWidget() {
	global $homeUrl;
	global $pages;
	
	$db = ssDbConnect();
	$queryList = httpParamsToSearchQuery();
	$settings = httpParamsToExtraQuery();
	$settings["type"] = "post";
	$settings["limit"] = "6";
	$postsData = generateSearchQuery ($queryList, $settings, 1, $db);
	
	if (! empty($postsData["errors"])) {
		foreach ($postsData["errors"] as $error) {
			print "<p class=\"ss-error\">$error</p>";
		}
	}
	
	else {
		global $sitename;
		print "<div class=\"widget-title\"><a title=\"Go to $sitename\" href=\"$homeUrl\" target=\"_blank\" >Now on<br />
		<img class=\"logo-wrapper\" src=\"$homeUrl/images/logos/SSLogoSmall.png\" alt=\"$sitename Logo\" /></a></div>
		<div id=\"posts-wrapper\">";
		
		if (isset($postsData["result"])) {
			while ($row = mysql_fetch_array($postsData["result"])) {
				$postId = $row["BLOG_POST_ID"];
				$blogName = $row["BLOG_NAME"];
				$blogUri = $row[ "BLOG_URI"];
				$postTitle = $row[ "BLOG_POST_TITLE"];
				$postUri = $row[ "BLOG_POST_URI"];
				
				// If post doesn't have a title, use the url instead.
				if (! $postTitle) $postTitle = $postUri;
				
				print "<div class=\"ss-entry-wrapper\">
				<a class=\"postTitle\" href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a><br /><a target=\"_blank\" class=\"blogTitle\" title=\"$blogName homepage\" href=\"$blogUri\">$blogName</a>
				</div>";
			}
		}
		else {
			print "<div class=\"padding-content\">No results found for your search parameters.</div>";
		}
		
		print "</div>";
	}
}
print "<!DOCTYPE html>
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
<title>Widget | $sitename</title>
<style type=\"text/css\">
* {
	padding: 0;
	margin: 0;
	font-family: Verdana, Arial, Helvetica, Sans-Serif;
	text-decoration: none;
	font-size: 12px;
}
html {
	height: 100%;
}
body {
	height: 100%;
	width: 100%;
	background: #F7F7F7;
}
a, a:visited {
	color: #975050;
}
a:hover, a:visited:hover {
	color: #630707;
}
#posts-wrapper {
	overflow: auto;
	height: 338px;
}
.widget-title, .widget-title a:visited, .widget-title a, .widget-title:hover {
	color: #525252;
	font-weight: bold;
	font-size: 0.85em;
	padding: 5px 0px;
	text-align: center;
	width: 100%;
}
#wrapper {
	width: 100%;
	min-height: 100%;
}
.logo-wrapper {
	margin-top: 5px;
	color: #8D4444;
	font-weight: bold;
	max-width: 90%;
}
.message {
	font-size: 1.4em; color: #B8B8B8; background: #1A1A1A; padding: 40px;
}
.blogTitle, a.blogTitle, a:visited.blogTitle {
	color: #888;
	margin-right: 10px;
	display: inline-block;
	font-size: 0.8em;
}
a:hover.blogTitle, a:hover:visited.blogTitle {
	color: black;
}
a:hover.postTitle {
	text-decoration: none;
	color: #410707;
}
a.postTitle, a:visited.postTitle {
	text-decoration: none;
	color: #BA1212;
}
a:hover.postTitle, a:visited:hover.postTitle {
	color: #630707;
	text-decoration: none;
}
a.postTitle, a:visited.postTitle {
	text-decoration: none;
	color: #BA1212;
}
.postTitle {
	font-size: 0.8em;
	font-weight: bold;
}
.ss-entry-wrapper:hover {
background: #EEE;
}
.ss-entry-wrapper {
padding: 4px;
}
</style>
</head>
<body>
<div id=\"wrapper\">";
displayWidget();
print "</div>
</body>
</html>";

?>
