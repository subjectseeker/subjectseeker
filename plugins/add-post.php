<?php

/*

Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function pluginAddPost() {
	$db	= ssDbConnect();
	
	if (!isLoggedIn()){
		print "<p class=\"ss-warning\">You must be logged in to use this feature.</p>";
		return NULL;
	}
	
	$authUser = new auth();
	$authUserId = $authUser->userId;
	$authUserName = $authUser->userName;
	$authUserPriv = getUserPrivilegeStatus($authUserId, $db);
	
	if ($authUserPriv < 1) {
		print "<p class=\"ss-warning\">You don't have the privileges to use this feature.</p>";
		return NULL;
	}
	
	$step = NULL;
	if (isset($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
	
	if ($step == NULL) {
		print "<form method=\"post\">
		<input type=\"hidden\" name=\"step\" value=\"postInfo\" />
		<div class=\"center-text\">
		<p class=\"margin-bottom-small\">Enter the URL of the post you wish to add</p>
		<div class=\"margin-bottom-small\"><input class=\"big-input\" type=\"text\" name=\"post-url\" size=\"40\" /></div>
		<p class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</p>\n
		<p><input class=\"big-button\" type=\"submit\" value=\"Next step\" /></p>
		</div>
		</form>";
		
	} elseif ($step == "postInfo") {
		addPostForm();
		
	} elseif ($step == "addPost") {
		$postId = NULL;
		$postDate = $_POST["postDate"];
		$postTitle = $_POST["postTitle"];
		$postSummary = $_POST["postSummary"];
		$postUrl = $_POST["postUrl"];
		$authorName = $_POST["authorName"];
		$siteId = $_POST["siteId"];
		$postStatus = NULL;
		
		$errors = checkPostData($postId, $postTitle, $postSummary, $postUrl, $postDate, $postStatus, NULL, $db);
		if (empty($postDate)) {
			$errors .= "<p class=\"ss-error\">You must submit a date for this post.</p>";
		}
		if (empty($postUrl)) {
			$errors .= "<p class=\"ss-error\">You must submit a URL for this post.</p>";
		}
		$sql = "SELECT * FROM BLOG_POST WHERE (BLOG_POST_TITLE = '$postTitle' AND BLOG_POST_DATE_TIME = '$postDate') OR (BLOG_POST_URI = '$postUrl')";
		$result =	mysql_query($sql, $db);
		if (mysql_num_rows($result) != 0) {
			$row = mysql_fetch_array($result);
			var_dump($row);
			$errors .= "<p class=\"ss-error\">This post already exists in the database.</p>";
		}
		if ($errors) {
			print "$errors";
			addPostForm();
			return NULL;
		}
		
		addPost($postTitle, $postUrl, $postDate, $postSummary, $authorName, $siteId, $db);
		print "<p class=\"ss-successful\">Post successfully added.</p>";
	}
	
}

function addPostForm() {
	$postUrl = $_REQUEST["post-url"];
	$doc = new DOMDocument();
	@$doc->loadHTMLFile($postUrl);
	$xpath = new DOMXPath($doc);
	$postTitle = $xpath->query("//title")->item(0)->nodeValue;
	
	print "<form method=\"post\">
	<input type=\"hidden\" name=\"step\" value=\"addPost\" />
	<input type=\"hidden\" name=\"post-url\" value=\"$postUrl\" />
	<h3>General Information</h3>
	<p>Post Date <span class=\"subtle-text\">(YYYY-MM-DD HH:MM:SS)</span><br />
	<input type=\"text\" name=\"postDate\" /></p>
	<p>Post Title<br />
	<input type=\"text\" name=\"postTitle\" size=\"40\" value=\"".htmlspecialchars($postTitle, ENT_QUOTES)."\"/></p>\n
	<p>Site ID<br />
	<input type=\"text\" name=\"siteId\" size=\"40\" /></p>\n
	<p>Post URL<br />
	<input type=\"text\" name=\"postUrl\" size=\"40\" value=\"".htmlspecialchars($postUrl, ENT_QUOTES)."\" /></p>
	<p>Summary <span class=\"subtle-text\">(Optional)</span><br />
	<textarea name=\"postSummary\" rows=\"5\" cols=\"60\"></textarea></p>\n
	<p>Author Name<br />
	<input type=\"text\" name=\"authorName\" size=\"40\" /></p>\n
	<input class=\"ss-button\" type=\"submit\" value=\"Add Post\" />
	</form>";
}

function addPost($postTitle, $postUrl, $postDate, $postSummary, $authorName, $siteId, $db) {
	
	$postDate = dateStringToSql($postDate);
	
	$authorName = "Unknown";
	$authorList = getAuthorList ($siteId, FALSE, $db);
	if (mysql_num_rows($authorList) == 1) {
		$row = mysql_fetch_array($authorList);
		$authorName = $row["BLOG_AUTHOR_ACCOUNT_NAME"];
	}
	if (isset($_POST["authorName"])) {
		$authorName = $_POST["authorName"];
	}
	$authorId = addBlogAuthor($authorName, $siteId, $db);
	
	$sql = "INSERT INTO BLOG_POST (BLOG_ID, BLOG_AUTHOR_ID, LANGUAGE_ID, BLOG_POST_STATUS_ID, BLOG_POST_URI, BLOG_POST_DATE_TIME, BLOG_POST_INGEST_DATE_TIME, BLOG_POST_SUMMARY, BLOG_POST_TITLE) VALUES ($siteId, $authorId, 33, 0, '". mysql_real_escape_string( htmlspecialchars($postUrl) ) . "' , '" . $postDate . "', NOW(), '" . mysql_real_escape_string($postSummary) . "' ,'" . mysql_real_escape_string($postTitle) . "')";
	mysql_query($sql, $db);
}

?>
