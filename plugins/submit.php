<?php

/*

Copyright © 2010–2012 Christopher R. Maden, Jessica Perry Hekman and Gabriel Aponte.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS,” WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function addSite() {
	// Connect to DB.
	$db	= ssDbConnect();
	global $pages;
	
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
	}
	else {
		print "<p class=\"ss-warning\">You can claim your site if you <a href=\"".$pages["login"]->getAddress(TRUE)."\" title=\"Log In Page\">log in</a>.</p>";
	}
	
	$step = NULL;
	$submitUrl = NULL;
	if (!empty($_REQUEST["step"])) {
		$step = $_REQUEST["step"];
	}
	if (!empty($_REQUEST["blogId"])) {
		$blogId = $_REQUEST["blogId"];
	}
	if (!empty($_REQUEST["submitUrl"])) {
		$submitUrl = $_REQUEST["submitUrl"];
	}

	if ($step === null) {
		if ($submitUrl == null) {
			displayShortBlogForm(null, $db);
		} else {
			displayBlogForm(null, $db);
		}
	} else if ($step === "blogInfo") {
		doAddBlog($db);
	} else if ($step === "verify") {
		if (! $authUserName) {
			print "<p class=\"ss-error\">Error: You must <a href=\"".$pages["login"]->getAddress(TRUE)."\" title=\"Log In Page\">log in</a> to claim a blog.</p>\n";
			return;
		}
		doVerifyClaim($blogId, $authUserName, $db);
	} else if ($step === "userAuthorLinkForm") {
		if (! $authUserName) {
			print "<p class=\"ss-error\">Error: You must <a href=\"".$pages["login"]->getAddress(TRUE)."\" title=\"Log In Page\">log in</a> to claim a blog.</p>\n";
			return;
		}
		doLinkUserAndAuthor($authUserId, $authUserName, $db);
	} else {
		print "<p class=\"ss-error\">ERROR: Unknown step $step.</p>";
	}
}

function displayShortBlogForm ($errormsg, $db) {
	global $pages;
	
	$submitUrl = NULL;
	if (!empty($_REQUEST["submitUrl"])) $submitUrl = $_REQUEST["submitUrl"];
	$authUserId;

	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
								$userPriv = getUserPrivilegeStatus($authUserId, $db);
	}

	print "<form method=\"post\">";

	 if ($errormsg !== null) {
		 print "$errormsg\n";
	 }

	print "<div class=\"center-text\">
	<p class=\"margin-bottom-small\">Enter either the URL of the site or the URL of the site's feed (RSS or Atom):</p>
	<div class=\"margin-bottom-small\"><input class=\"big-input\" type=\"text\" name=\"submitUrl\" size=\"40\" value=\"$submitUrl\" /></div>
	<p class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</p>\n
	<p><input class=\"big-button\" type=\"submit\" value=\"Next step\" /></p>
	</div>
	</form>";
}

function displayBlogForm ($errormsg, $db) {
	global $pages;
	
	$blogName = NULL;
	$blogUri = NULL;
	$blogSyndicationUri = NULL;
	$blogDescription = NULL;
	if (isset($_REQUEST["blogname"])) $blogName = $_REQUEST["blogname"];
	if (isset($_REQUEST["blogurl"])) $blogUri = htmlspecialchars($_REQUEST["blogurl"]);
	if (isset($_REQUEST["blogsyndicationuri"])) $blogSyndicationUri = htmlspecialchars($_REQUEST["blogsyndicationuri"]);
	if (isset($_REQUEST["blogdescription"])) $blogDescription = $_REQUEST["blogdescription"];

	$authUserId = NULL;
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
	}

	// TODO: Looks like we are not using this error area, remove it?
	if ($errormsg !== null) {
		print "<p class=\"ss-error\">$errormsg</p>\n";
	}

	// Attempt to prepopulate from URL if submitUrl param set
	if (!empty($_REQUEST["submitUrl"])) {
		$submitUri = $_REQUEST["submitUrl"];
	}
	if ($submitUri != NULL) {
		$feed = getSimplePie($submitUri);
		$blogName; $blogUri; $blogDescription; $blogSyndicationUri;

	 if ($feed->error()) {
		 print "<p class=\"ss-error\">Unable to find feed for $submitUri. You can enter the address manually below.</p>\n";
	 } else {
		 $blogName = $feed->get_title();
		 $blogUri = $feed->get_link();
		 $blogDescription = $feed->get_description();
		 $blogSyndicationUri = $feed->subscribe_url();

		 $blogId = getBlogByAltUri($blogUri, $db);
		 if (!empty($blogId)) {
			 print "<p class=\"ss-error\">This site is already in the system.</p>\n";
		 } else {
			 $blogId = getBlogByAltSyndicationUri($blogSyndicationUri, $db);
			 if ($blogId != null) {
				 print "<p class=\"ss-error\">This feed is already in the system.</p>\n";
			 }
		 }
		 // TODO: if blog/feed already found, take us to the profile page for the blog -- once we have one
	 }
 }
 
 submitBlogForm ($blogName, $blogUri, $blogDescription, $blogSyndicationUri, NULL, $authUserId, $db);

}

function submitBlogForm ($blogName, $blogUri, $blogDescription, $blogSyndicationUri, $twitterHandle, $authUserId, $db) {
	print "<form method=\"post\">
<input type=\"hidden\" name=\"step\" value=\"blogInfo\" />
	<h3>General Information</h3>
	<p>Site Name<br />
	<input type=\"text\" name=\"blogName\" size=\"40\" value=\"".htmlspecialchars($blogName, ENT_QUOTES)."\"/></p>\n
	<p>Homepage URL<br />
	<input type=\"text\" name=\"blogUri\" size=\"40\" value=\"".htmlspecialchars($blogUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(Must start with \"http://\", e.g., <em>http://blogname.blogspot.com/</em>.)</span></p>\n
	<p>Feed URL<br />
	<input type=\"text\" name=\"blogSyndicationUri\" size=\"40\" value=\"".htmlspecialchars($blogSyndicationUri, ENT_QUOTES)."\" /><br /><span class=\"subtle-text\">(RSS or Atom feed. Must start with \"http://\", e.g., <em>http://feeds.feedburner.com/blogname/</em>.)</span></p>
	<p>Description <span class=\"subtle-text\">(Optional)</span><br />
	<textarea name=\"blogDescription\" rows=\"5\" cols=\"60\">$blogDescription</textarea></p>\n
	<p>Categories: <select name='topic1'>\n
	<option value='-1'>None</option>\n";
	$topicList = getTopicList(true, $db);
	while ($row = mysql_fetch_array($topicList)) {
		print "<option value='" . $row["TOPIC_ID"] . "'>" . $row["TOPIC_NAME"] . "</option>\n";
	}
	print "</select>&nbsp;<select name='topic2'>\n";
	print "<option value='-1'> None</option>\n";
	$topicList = getTopicList(true, $db);
	while ($row = mysql_fetch_array($topicList)) {
		print "<option value='" . $row["TOPIC_ID"] . "'> " . $row["TOPIC_NAME"] . "</option>\n";
	}
	print "</select></p>\n<p>";
	if (!empty($authUserId)) {
		print "<p><input type=\"checkbox\" name=\"userIsAuthor\" /> I want to be identified as an author of this blog.</p>";
	}
	print "<hr class=\"margin-bottom\" />
	<h3>Social Networks</h3>
	<p>Twitter Handle <span class=\"subtle-text\">(Optional)</span><br />
	<input type=\"text\" name=\"twitterHandle\" size=\"40\" value=\"".htmlspecialchars($twitterHandle, ENT_QUOTES)."\"/></p>\n
	<hr class=\"margin-bottom\" />
	<input class=\"ss-button\" type=\"submit\" value=\"Add\" />
	</form>";
}

function doAddBlog ($db) {
	global $pages;
	
	$blogName = NULL;
	$blogUri = NULL;
	$blogSyndicationUri = NULL;
	$blogDescription = NULL;
	$topic1 = NULL;
	$topic2 = NULL;
	$twitterHandle = NULL;
	$userIsAuthor = NULL;
	if (!empty($_REQUEST["blogName"])) $blogName = $_REQUEST["blogName"];
	if (!empty($_REQUEST["blogUri"])) $blogUri = $_REQUEST["blogUri"];
	if (!empty($_REQUEST["blogSyndicationUri"])) $blogSyndicationUri = $_REQUEST["blogSyndicationUri"];
	if (!empty($_REQUEST["blogDescription"])) $blogDescription = $_REQUEST["blogDescription"];
	if (!empty($_REQUEST["topic1"])) $topic1 = $_REQUEST["topic1"];
	if (!empty($_REQUEST["topic2"])) $topic2 = $_REQUEST["topic2"];
	if (!empty($_REQUEST["twitterHandle"])) $twitterHandle = str_replace("@", "", $_REQUEST["twitterHandle"]);
	if (!empty($_REQUEST["userIsAuthor"])) $userIsAuthor = $_REQUEST["userIsAuthor"];
	
	$authUserId = NULL;
	$authUserName = NULL;
	$userPriv = NULL;
	// Only get user info if logged in
	if (isLoggedIn()){
		$authUser = new auth();
		$authUserId = $authUser->userId;
		$authUserName = $authUser->userName;
		$userEmail = getUserEmail($authUserId, $db);
		$userPriv = getUserPrivilegeStatus($authUserId, $db);
	}
	
	$errors = checkBlogData(NULL, $blogName, $blogUri, $blogSyndicationUri, $blogDescription, NULL, $topic1, $topic2, $twitterHandle, $authUserId, $db);
	
	if (!empty($errors)) {
		print "$errors";
		submitBlogForm ($blogName, $blogUri, $blogDescription, $blogSyndicationUri, $twitterHandle, $authUserId, $db);
	}
	else {
		$addBlog = addBlog($blogName, $blogUri, $blogSyndicationUri, $blogDescription, $topic1, $topic2, $authUserId, $db);
	
		$blogId = $addBlog["id"];
		
		if (!empty($twitterHandle)) {
			$twitterUser = getTwitterUserDetails($twitterHandle);
				
			if (isset($twitterUser->id)) {
				unlinkSocialNetworkSite(1, $blogId, $db);
				addSocialNetworkUser(1, $twitterUser->id, $twitterUser->screen_name, $twitterUser->profile_image_url, NULL, $blogId, NULL, NULL, NULL, $db);
			}
		}
	
		if (empty($addBlog["errormsg"])) {
			echo "<p class=\"ss-successful\">Successfully added site to the system.</p>";
			if ($userPriv == 0) {
				echo "<p>This site will not be publicly displayed in the system until it has been approved by an editor.</p>";
			}
			if (!empty($userIsAuthor) && $userIsAuthor == TRUE && !empty($userEmail)) {
				global $sitename;
				global $contactEmail;
				$subject = "Site Submission Status: Pending";
				$message = "Hello, ".$authUserName."!

Thanks for submitting ".$blogName." to ".$sitename.".
					
Before this site appears in the system, it must be approved by one of our editors. We will notify you when this happens if you have claimed your site.
					
If you have any questions, feel free to contact us at ".$contactEmail."
					
The ".$sitename." Team.";
				sendMail($userEmail, $subject, $message);
			}
			else {
				echo "<p><a class=\"ss-button\" href=\"".$pages["home"]->getAddress()."\">Go to Home Page</a> <a class=\"ss-button\" href=\"".$pages["submit"]->getAddress()."\">Submit another site</a></p>";
			}
		} else {
			// Blog is already in the system.
			print "<p class=\"ss-error\">ERROR: " . $addBlog["errormsg"] . "</p>\n";
			print "<p class=\"info\">This could be because it was pre-populated in our database, someone else submitted it, or because our editors rejected it.</p><p class=\"info\">If it was rejected, you should have received an email from us explaining why.</p><p class=\"info\">Otherwise, you can <a href=\"".$pages["home"]->getAddress()."claim/$blogId\">claim the blog</a> to show that you are (one of) the author(s). See our <a href=\"".$pages["help"]->getAddress()."\">help pages</a> for more information.</p>\n";
			return;
		}
		
		if ($userIsAuthor === "on") {
			doClaimBlog($blogId, $authUserName, $db);
		}
	}
}

?>
