<?php

function displaySlideShow() {
	global $thirdPartyUrl;
	print "<script type=\"text/javascript\" src=\"".$thirdPartyUrl."/pikachoose/lib/jquery.pikachoose.js\"></script>
	<script type=\"text/javascript\">
	$(document).ready(function() {
		$(\"#pikame\").PikaChoose({speed:10000, transition:[2,3,5]});
	});
	</script>";
	
	global $imagesUrl;
				$db = ssDbConnect();
	print "<div class=\"pikachoose\">
	<ul id=\"pikame\">";
	$recommendations = getEditorsPicks("images", $db);
	while ($row = mysql_fetch_array($recommendations)) {
		$imageName = $row["REC_IMAGE"];
		$postData = getPost($row["BLOG_POST_ID"], $db);
		$title = $postData["BLOG_POST_TITLE"];
		$url = htmlspecialchars($postData["BLOG_POST_URI"]);
		
		print "<li><a href=\"$url\" target=\"_blank\"><img src=\"$imagesUrl/headers/$imageName\" alt=\"Header Image\" /></a><span><a class=\"ss-bold\" href=\"$url\" target=\"_blank\">$title</a></span></li>";
	}
	print "</ul></div>";
}
?>