<?php

/*

Copyright © 2010–2012 Christopher R. Maden and Jessica Perry Hekman.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

include_once (dirname(__FILE__)."/../initialize.php");

// Only recommend if user is logged in.
if (isLoggedIn()) {
	$db = ssDbConnect();
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserPriv = getUserPrivilegeStatus($authUserId, $db);
	$postId = $_REQUEST["id"];
	$type = $_REQUEST["type"];

	if ($type == "unknown") {
		mysql_select_db("sciseek_openlab", $db);
	}

	$sql = "SELECT *, topic.TOPIC_NAME FROM TAG tag INNER JOIN TOPIC topic ON tag.TOPIC_ID = topic.TOPIC_ID WHERE OBJECT_ID = '$postId' AND OBJECT_TYPE_ID = '1' AND TOPIC_NAME = 'openlab-2013-finalist'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_array($result);
	
	$tagName = "openlab-2013-finalist";
	$tagId = addTag($tagName, $postId, 1, 3, $authUserId, TRUE, $db);
	if ($row) {
		$sql = "DELETE FROM TAG WHERE TAG_ID = '$tagId'";
		mysql_query($sql, $db);
		print "<div class='nominate' title='Nominate'></div>";
	} else {
		print "<div class='nominated' title='Nominated'></div>";
	}
	
	
	
	/*global $homeUrl, $sitename, $contactEmail;
	
	$post = getPost($postId, $db);
	$postUrl = $post["BLOG_POST_URI"];
	
	$subject = "ScienceSeeker Awards Nomination";
	$message = "There is a new nomination for the ScienceSeeker Awards.

$homeUrl/post/$postId

The ".$sitename." Team.";
	sendMail($contactEmail, $subject, $message);*/
}

?>