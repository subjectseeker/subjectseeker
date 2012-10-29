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
	} else {
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
		} else {
			print "<div class=\"padding-content\">No results found for your search parameters.</div>";
		}
		print "</div>";
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Widget | <?php echo $sitename ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo $themeUrl; ?>/widget.css" media="all">
<?php
if (!empty($localStylesheet)) {
?>
<link rel="stylesheet" type="text/css" href="<?php echo $localStylesheet ?>" media="all" />
<?php
}
?>
</head>
<body>
<div id="wrapper">
<?php
displayWidget();
?>
</div>
</body>
</html>

?>
