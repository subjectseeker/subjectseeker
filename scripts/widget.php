<?php
/*
Plugin Name: SubjectSeeker Display Feed
Plugin URI: http://scienceseeker.org/
Description: Display feed, filtered as requested
Author: Liminality
Version: 1.0
Author URI: http://scienceseeker.org
*/

/*
 * PHP widget methods
 */

include_once "ss-globals.php";
include_once "ss-util.php";
require_once "/home/sciseek/public_html/dev/wp-load.php";

function displayWidget() {
	
	$db = ssDbConnect();
	$queryList = httpParamsToSearchQuery();
	$settings = httpParamsToExtraQuery();
	$errormsgs = array();
	$settings["type"] = "post";
	$settings["limit"] = "6";
	$postsData = generateSearchQuery ($queryList, $settings, 1, $errormsgs, $db);
	
	if (! empty($errormsgs)) {
		print "<div id=\"padding-content\">";
		foreach ($errormsgs as $error) {
			print "<p>Error: $error</p>";
		}
		print "</div>";
	}
	
	else {
		global $mainFeed;
		if (is_user_logged_in()){
			global $current_user;
			$db = ssDbConnect();
			get_currentuserinfo();
			$displayName = $current_user->user_login;
			$email = $current_user->user_email;
			$userId = addUser($displayName, $email, $db);
			$userPriv = getUserPrivilegeStatus($userId, $db);
		}
		
		print "<div class=\"widget-title\">Now on ScienceSeeker</div>
		<div id=\"posts-wrapper\">";
		
		if ($postsData) {
			while ($row = mysql_fetch_array($postsData)) {
				$postId = $row["BLOG_POST_ID"];
				$blogName = $row["BLOG_NAME"];
				$blogUri = $row[ "BLOG_URI"];
				$postTitle = $row[ "BLOG_POST_TITLE"];
				$postUri = $row[ "BLOG_POST_URI"];
				
				// If post doesn't have a title, use the url instead.
				if (! $postTitle) $postTitle = $postUri;
				
				print "<div class=\"ss-entry-wrapper\">
				<a class=\"postTitle\" href=\"$postUri\" target=\"_blank\" rel=\"bookmark\" title=\"Permanent link to $postTitle\">$postTitle</a><br /><a target=\"_blank\" class=\"blogTitle\" title=\"Go to $blogName home page\" href=\"$blogUri\">$blogName</a>
				</div>";
			}
		}
		else {
			print "<div id=\"padding-content\">No results found for your search parameters.</div>";
		}
		
		print "</div>";
	}
	global $sitename;
	global $homeUrl;
	print "<div style=\"position: fixed; bottom: 0px; width: 100%; text-align:center; color: black; font-size: 0.8em;\">
	<div class=\"footer-wrapper\"><a title=\"Go to $sitename\" href=\"$homeUrl\"><img style=\"max-width: 90%;\" src=\"/images/logos/ScienceSeekerLogoSmall.png\"></a></div></div>";
}

print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />
<title>Widget | ScienceSeeker: Science News Aggregator</title>
<link rel=\"shortcut icon\" href=\"http://dev.scienceseeker.org/wp-content/themes/eximius/images/favicon.ico\" type=\"image/x-icon\">
<link rel=\"stylesheet\" type=\"text/css\" href=\"http://dev.scienceseeker.org/wp-content/themes/eximius/style.css\" media=\"all\">
<style type=\"text/css\">
* {
	padding: 0;
	margin: 0;
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
#posts-wrapper {
	overflow: auto;
	max-height: 336px;
}
.widget-title {
	color: #525252;
	font-weight: bold;
	font-size: 0.8em;
	padding: 5px;
	text-align: center;
}
#wrapper {
	width: 100%;
	min-height: 100%;
}
.footer-wrapper {
	margin: 5px 5px 10px 5px;
	color: #8D4444;
	font-weight: bold;
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
	color: black;
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
padding: 6px;
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
