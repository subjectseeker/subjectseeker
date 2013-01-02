<?php
/*
Copyright © 2010–2012 Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

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
	$recommendations = getEditorsPicks($db);
	while ($row = mysql_fetch_array($recommendations)) {
		$imageName = $row["REC_IMAGE"];
		$postData = getPost($row["OBJECT_ID"], $db);
		$title = $postData["BLOG_POST_TITLE"];
		$url = htmlspecialchars($postData["BLOG_POST_URI"]);
		
		print "<li><a href=\"$url\" target=\"_blank\"><img src=\"$imagesUrl/headers/$imageName\" alt=\"Header Image\" /></a><span><a class=\"ss-bold\" href=\"$url\" target=\"_blank\">$title</a></span></li>";
	}
	print "</ul></div>";
}

// Input: DB handle
// Return: Post IDs and Image names
function getEditorsPicks($db) {
	$sql = "SELECT rec.OBJECT_ID, rec.REC_IMAGE FROM RECOMMENDATION rec, USER user WHERE rec.USER_ID = user.USER_ID AND user.USER_PRIVILEGE_ID > 0 AND rec.REC_IMAGE <> '' AND rec.OBJECT_TYPE_ID = '1' ORDER BY REC_DATE_TIME DESC LIMIT 4";
	$results = mysql_query($sql, $db);
	
	return $results;
}
?>